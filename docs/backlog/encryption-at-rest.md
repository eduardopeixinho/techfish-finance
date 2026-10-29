# Backlog — Encryption at Rest (campos monetários)

> **Status:** não implementado. Decisão consciente de **manter plaintext** por
> ora. Ver seção "Por que não agora" no fim.
>
> **Criado em:** 2026-06-06
> **Owner:** pendente
> **Esforço estimado:** 2-3 horas (código + testes + backfill)
> **Risco:** médio (irreversível se a APP_KEY for perdida; reversível com
> cuidado se rodar a migration de backfill em todas as linhas)

---

## Contexto

O TechFish guarda saldos, valores de transações e faturas em **plaintext**
no MySQL. Isso significa que:

- Um `mysqldump` do banco (dev, backup, staging) expõe todos os valores.
- Um DBA / admin do MySQL lê tudo com `SELECT *`.
- Um backup em S3/GDrive sem criptografia própria, idem.

A camada de defesa atual é:

| Camada | Status | Cobertura |
|---|---|---|
| HTTPS in transit | OK (em prod) | Cliente ↔ servidor |
| APP_KEY em local seguro | OK (convenção Laravel) | Chaves de sessão, cookies |
| Policies / Gates | OK | Acesso via app (`auth`, `admin.access`) |
| Storage encryption (TDE) | Depende do provedor | Disco do MySQL |
| **Criptografia de campos (app-level)** | **NÃO IMPLEMENTADO** | **—** |

Este documento cobre a **5ª camada**: criptografia campo-a-campo na camada
de aplicação, usando o `encrypted` cast do Laravel (AES-256-CBC via
`Crypt` facade, chave = `APP_KEY`).

---

## O que criptografar e o que NÃO

### Criptografar (alto valor, baixo custo de UX)

Campos monetários — leitura/escrita pontual, sem queries de filtro/ordenação.

| Model | Campo | Cast |
|---|---|---|
| `Finance\Transaction` | `amount` | `encrypted:decimal:2` |
| `Finance\Account` | `balance` (se persistido) | `encrypted:decimal:2` |
| `Finance\Account` | `name` (opcional, pode ter info pessoal) | `encrypted` |
| `CreditCardBill` (faturas) | `total_amount`, `paid_amount`, `minimum_payment` | `encrypted:decimal:2` |
| `Investment\InvestmentTransaction` | `amount`, `price` | `encrypted:decimal:2` |

### NÃO criptografar (quebraria queries/relatórios)

- `id`, `user_id`, `created_at`, `updated_at` — joins, ordenação, auditoria.
- `transaction_date`, `due_date`, `competency` — `WHERE date BETWEEN`
  deixa de funcionar; `ORDER BY date` idem.
- `account_id`, `category_id` (FKs) — joins.
- `description` (depende) — se for usado em `LIKE '%mercado%'`, criptografar
  quebra a busca. Avaliar caso a caso.
- `type` (income/expense) — usado em filtros e agregações.

### Regra prática

> **Criptografe valores (dados sensíveis numéricos) que você só lê/escreve
> pontualmente. Não criptografe dados que você filtra, ordena ou agrega
> em SQL.**

---

## Trade-offs (custos reais)

Criptografar campo-a-campo com Laravel `encrypted` cast significa:

| Operação | Plaintext | Criptografado |
|---|---|---|
| `SELECT * WHERE user_id = ?` | Rápido | Rápido (`user_id` em claro) |
| `SUM(amount) GROUP BY category` | 1 query, instantâneo | **Fetch all + decrypt + sum em PHP**, lento |
| `ORDER BY amount` | Usa índice | **Decrypt + sort em PHP**, sem índice |
| `WHERE amount > 1000` | Usa índice | **Full scan + decrypt** |
| `WHERE description LIKE '%mercado%'` | Rápido | **Não funciona** (sem blind index) |
| Relatórios mensais (FinanceReportService) | 1 query agregada | **Fetch all + decrypt + reduce** |

**Para o TechFish hoje** (alguns milhares de transações por usuário no pior
caso), isso é aceitável — PHP faz reduce de arrays de 5k itens em < 50ms.
Em 100k+ linhas, vai doer.

**Workarounds avançados** (NÃO recomendados por ora):
- **Blind indexes** (HMAC-SHA256 do valor) — queries de equality, mas
  custo de manutenção e storage.
- **Deterministic encryption** — mesmo valor → mesmo ciphertext, mas
  quebram o ponto da criptografia se o atacante tem exemplos conhecidos.
- **Partial homomorphic encryption** (Intel SGX, OpenMined) — overkill.

---

## O que a indústria faz (referência)

- **Bancos tradicionais** (Itaú, Bradesco): TDE no storage + criptografia
  campo-a-campo para identificadores pessoais (CPF, conta). Saldos em data
  warehouse separado.
- **Bancos digitais** (Nubank, C6): TDE + criptografia adicional para
  dados sensíveis. Data warehouses pseudonimizados para queries.
- **Apps pessoais** (Mint, YNAB, Mobills, Organizze): em geral **não**
  criptografam campo-a-campo. Confiam em TDE do provedor + auth forte.
- **Fintech B2B / regulada** (Stone, Conta Azul): criptografia campo-a-campo
  é comum para dados pessoais (CNPJ, conta). Valores transacionais ficam
  em data warehouse separado.

---

## Plano de implementação (quando for a hora)

### 1. Models — adicionar cast `encrypted`

```php
// app/Models/Finance/Transaction.php
protected function casts(): array
{
    return [
        'amount' => 'encrypted:decimal:2',
        'transaction_date' => 'date',
        // ...
    ];
}
```

A string `decimal:2` preserva a precisão (`1.005` vira `1.01`, idêntico a
`Format::brl`).

### 2. Backfill de dados existentes (CRÍTICO)

O cast `encrypted` criptografa **na escrita**. Dados antigos em plaintext
continuam legíveis enquanto não forem re-salvos. Migration obrigatória:

```php
// database/migrations/YYYY_MM_DD_HHMMSS_encrypt_existing_amounts.php
public function up(): void
{
    // Para cada registro: re-save para acionar o cast
    Transaction::query()->chunk(200, function ($transactions) {
        foreach ($transactions as $tx) {
            // Toque o campo para forçar re-serialização
            $tx->amount = $tx->getAttributes()['amount']; // valor raw
            $tx->save();
        }
    });

    // Idem para Account, CreditCardBill, InvestmentTransaction
}
```

**Cuidado:**
- Rodar em **janela de manutenção** em prod (tranca a tabela por minutos).
- **Testar em staging primeiro** com cópia do banco.
- **Backup do banco antes** (para rollback se algo der errado).
- O cast é na camada Eloquent; **SQL puro não criptografa**.

### 3. Refatorar services com queries agregadas

Os serviços abaixo usam `SUM`, `AVG`, `GROUP BY` no SQL — vão precisar virar
`SELECT + decrypt + reduce` em PHP:

- `app/Services/DashboardMetricsService.php` (10 métricas agregadas)
- `app/Services/FinanceReportService.php` (relatórios)
- `app/Services/InvestmentReportService.php` (carteira, proventos)
- `app/Services/CreditCardBillService.php` (totais de fatura)

**Padrão de refator:**
```php
// Antes (SQL agregada)
$totalBalance = Account::where('user_id', $userId)->sum('balance');

// Depois (fetch + decrypt + sum)
$totalBalance = Account::where('user_id', $userId)
    ->get(['balance'])
    ->sum(fn ($a) => (float) $a->balance);
```

### 4. Testes

Cobertura mínima:

- `Transaction::create([...])` grava **ciphertext** no DB (não plaintext).
- `Transaction::find($id)->amount` retorna o **float original**.
- Queries de filtro por `transaction_date` (em claro) continuam funcionando.
- `SUM` em PHP dá o mesmo resultado que dava em SQL.
- `getAttributes()['amount']` retorna ciphertext; `->amount` retorna float.

### 5. AGENTS.md

Adicionar regra:
> "Campos monetários são `encrypted` (Laravel cast). Queries de agregação
> são feitas em PHP, não SQL. Nunca usar `DB::raw('SUM(amount)')` em campo
> criptografado — sempre `->get()->sum(fn ...)`. Ver
> `docs/backlog/encryption-at-rest.md` para rationale completo."

### 6. APP_KEY — cuidado operacional

- **Nunca rotacionar `APP_KEY` em prod sem migration de dados** — dados
  antigos criptografados com a chave velha viram lixo.
- Considerar key rotation policy (ex: anual) **só** com vault (AWS KMS,
  HashiCorp Vault, Laravel Envoyer).
- **Backup da APP_KEY** em local seguro, fora do repo.
- Em `.env.example`, documentar: "Rotação exige backfill; ver
  `docs/backlog/encryption-at-rest.md`."

---

## Por que não agora (decisão registrada)

Considerando o porte (pessoal, milhares de linhas por usuário) e o tipo de
ameaça que esta camada defende (dump em dev, backup exposto, DBA lendo o
banco), a decisão de **manter como está** se baseia em:

1. **Ameaça de baixo impacto**: alguém com acesso ao MySQL já passou por
   várias camadas (acesso físico ou SSH ao servidor, permissões MySQL,
   conhecimento do schema). A criptografia campo-a-campo é **mais uma
   camada**, não correção de uma falha atual.
2. **TDE do provedor**: se o deploy for em AWS RDS / Aurora / DigitalOcean
   Managed MySQL, o storage já está criptografado em disco. Camada
   adicional é marginal.
3. **Custo de UX de queries**: relatórios, gráficos e filtros ficam mais
   lentos e complexos. Para o volume atual, **o trabalho de implementar
   + manter não compensa** o ganho marginal.
4. **Sem dados de produção críticos ainda**: o app está em dev/uso
   pessoal; o impacto de um vazamento é baixo (dados financeiros do
   próprio dono, que já tem acesso).

**Revisitar quando:**
- Volume de transações passar de ~50k por usuário (queries começam a doer).
- Decidir vender o app / oferecer como SaaS (ameaça muda de perfil).
- Exigência regulatória (LGPD com compartilhamento com terceiros, Open
  Finance, etc.).
- Decidir hospedar em infra própria (sem TDE do provedor).
- O usuário pedir explicitamente.

---

## Referências

- Laravel Docs — [Encryption](https://laravel.com/docs/encryption)
- Laravel Docs — [Eloquent Mutators / Casts](https://laravel.com/docs/eloquent-mutators)
- PCI-DSS v4.0 — Requirements 3.5, 3.6 (proteção de dados armazenados)
- LGPD Art. 46 (medidas de segurança) — criptografia é **uma** das medidas
- Open Finance Brasil — Padrão de Segurança (dados sensíveis)
- OWASP — [Cryptographic Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html)
