# AGENTS.md — TechFish Finance

Aplicação Laravel de finanças pessoais e carteira de investimentos (projeto de portfólio, open source).
Login é **exclusivamente** Google OAuth (Socialite), mais um **modo demonstração** opcional (`DEMO_MODE`).
Stack: Laravel 13, PHP 8.3+ (Docker usa 8.4), MySQL 8, Redis, Vite 5, Tailwind 3, Alpine 3.
Testes com **PHPUnit 12** (não é Pest).

## Ambiente

Tudo roda em Docker. Não execute `php artisan`, `composer` ou `npm` na máquina — entre no container
(exceção: `npm run build`, porque o container não tem Node; `public/build` é versionado).

```bash
./setup.sh                                            # .env, APP_KEY, build, migrate, seeders, conta demo
docker compose exec app php artisan <cmd>
docker compose exec app composer <cmd>
docker compose exec app php artisan test              # toda a suíte
docker compose exec app php artisan test --filter=... # uma classe/método
```

URLs locais: app `http://localhost:8000`, phpMyAdmin `http://localhost:8081` (root/rootsecret).
MySQL e Redis ficam expostos só em `127.0.0.1` (portas 33060 e 63790), para rodar ao lado de outros projetos.

## Arquitetura

- `app/Http/Controllers/Finance/` — painel, transações, contas, categorias; `Finance/Settings/` — tipos de conta, receita, meios de pagamento.
- `app/Http/Controllers/Investment/` — carteira, transações, proventos, tipos.
- `app/Http/Controllers/CreditCardBillController.php` — faturas (delega para `CreditCardBillService`).
- `app/Http/Controllers/Admin/` — RBAC (`RoleController`, `PermissionController`, `UserRoleController`, gate `admin.access`).
  O `UserRoleController` também desativa/reativa usuários (`users.deactivated_at`, gate `admin.users.deactivate`:
  só admin, nunca a si mesmo nem outro admin). Desativado não entra: o callback do Google recusa, o middleware
  `EnsureUserIsActive` (grupo `web`) derruba a sessão e o Sanctum recusa os tokens.
  Perfis `is_system` mantêm o slug ao serem renomeados (`User::isAdmin()` depende do slug `admin`).
- `app/Http/Controllers/AuthController.php` — Google OAuth. `DemoLoginController` — login da conta demo.
- `app/Services/` — `CreditCardBillService`, `FinanceReportService`, `InvestmentPositionService`, `InvestmentReportService`, `TransactionService`.
  Regra de negócio pesada aqui, **não** em controllers.
- `app/Http/Requests/{Admin,Finance,Investment}/` — Form Requests. Nunca valide inline no controller.
- Policies em `app/Policies/` (mapa em `app/Providers/AuthServiceProvider.php`, que também define os gates).

Rotas em `routes/web.php`: públicas só `/`, `/login/redirect`, `/auth/google/callback` e `POST /login/demo`;
o resto está no grupo `auth`. `/dashboard` redireciona para `/finance/dashboard` (destino padrão pós-login).

## Auth, Google OAuth e modo demonstração

- O callback do Google busca o `User` por `google_id` (ou e-mail no primeiro vínculo) e, no primeiro vínculo de
  uma conta pré-existente, troca senha e `remember_token` (anti pré-sequestro) e regenera a sessão.
- `GOOGLE_ALLOWED_DOMAINS` (CSV) é lido de `config('services.google.allowed_domains')`. Nunca use `env()` fora de `config/`.
- **Não reative** `/register`, `POST /login` nem reset de senha.
- Modo demonstração: `config/demo.php` (`DEMO_MODE`, `DEMO_EMAIL`). O `DemoSeeder` cria/reseta a conta demo com dados
  fictícios (idempotente). `User::isDemo()`: a conta demo não pode editar o perfil, ser excluída nem virar admin
  (o `RolePermissionSeeder` ignora a conta demo ao escolher o primeiro admin).

## Banco de dados e seeders

- MySQL 8 no Docker (db `techfish_finance`); testes em SQLite **em memória**.
- Seeders: `RolePermissionSeeder`, `InvestmentTypeSeeder`, `SettingsSeeder` (catálogos, chamados pelo `DatabaseSeeder`)
  e `DemoSeeder` (conta de demonstração). Para testes, use as factories.

## Testes

- Framework: **PHPUnit 12**. Pest NÃO está em uso.
- Banco: SQLite em memória + `RefreshDatabase` em quase todas as classes.
- **Isolamento do ambiente de teste (crítico)**: o container Docker exporta `APP_ENV=local`,
  `DB_CONNECTION=mysql` e `*_DRIVER=redis`, e o Laravel lê `$_SERVER` antes de `$_ENV`. Por isso o
  `phpunit.xml` declara cada variável também como `<server ... force="true">`. Sem isso a suíte rodava
  no MySQL de desenvolvimento e o `RefreshDatabase` **apagava o banco local** a cada execução.
  `tests/TestCase::setUpTraits()` aborta a suíte se não estiver em `testing` / `sqlite` / `:memory:`.
  Não remova nenhum dos dois. `LOG_CHANNEL=null` nos testes: a suíte não escreve no `storage/logs/laravel.log`.
- SQL tem que ser portável (MySQL em prod, SQLite nos testes): nada de `YEAR()`, `MONTH()`,
  `DATE_FORMAT()` em `selectRaw` — agrupe por mês em PHP (ver `FinanceReportService::getMonthlyEvolution`).
- `tests/TestCase.php` já chama `withoutMiddleware(VerifyCsrfToken::class)` no `setUp()` — **não** duplicar essa lógica nos testes.
- Helper útil: `tests/Traits\HasAdminUser.php` expõe `createAdminUser()` (cria usuário + `Role::factory()->admin()`) e `createRegularUser()`. Use em vez de criar role na mão.
- `RoleFactory` tem os states `admin()`, `inactive()`, `system()`.
- Estrutura: `tests/Unit/` (praticamente vazio) e `tests/Feature/{Admin,Auth,Finance,Finance/Settings,Investment,Integration}`. Espelhe essa divisão ao adicionar testes.
- O `phpunit.xml` traz uma `APP_KEY` só de teste e força `DEMO_MODE=false`: a suíte roda sem `.env` (ex.: CI).
- Para regressões, escreva o **menor** teste Feature que cubra o bug; valide status HTTP, redirect, e `assertDatabaseHas`/`assertDatabaseMissing` quando o efeito for persistência.
- Filas, e-mails e eventos: use os fakes do Laravel (`Mail::fake()`, `Bus::fake()`, `Event::fake()`, `Queue::fake()`).

## Convenções do projeto

- **Responder em português** em comentários, mensagens de usuário, e respostas ao usuário final.
- Priorizar **clareza, compatibilidade e manutenção** sobre magia. Em código financeiro, **sempre** explicite unidades (R$, datas, competência vs. caixa) — não confie em cast silencioso.
- Mudanças pequenas, incrementais e reversíveis. Apresente um plano curto antes de editar vários arquivos.
- Não refatorar por estética, não criar abstração nova sem ganho claro, não alterar arquivos fora do escopo.
- Não commitar: segredos, dumps de banco, `.env`, artefatos em `storage/`, `.phpunit.result.cache`, `.opencode/`, `opencode.json` (tudo já está no `.gitignore`).

## Layout e navegação

- `<x-finance-layout>` (Finanças, Investimentos e também o perfil, via `AppLayout`) e `<x-admin-layout>` (Admin).
  Ambos usam `<x-app-header />`: marca, `<x-main-nav />`, `<x-value-toggle />` e `<x-user-menu />`.
- `<x-main-nav />`: Painel · Transações · Contas · Faturas · Investimentos · Relatórios · [Admin]. O item ativo
  vem da rota atual (`request()->routeIs`). Abaixo de `lg` vira menu compacto. "Admin" só com `Gate::allows('admin.access')`.
- `welcome.blade.php` é a página inicial pública (logado é redirecionado ao painel); `layouts/guest` é o do login.
- `<title>` e migalha de navegação vêm de `app/Support/PageTitle.php` (adicionar rota = adicionar entrada).

## Componentes de UI (Finanças, Investimentos e Admin)

Todas as telas de `finance/*`, `investment/*` e `admin/*` usam os componentes em
`resources/views/components/ui/` — referência completa: `finance/categories/*`.
Os componentes de formulário herdados do Breeze (`text-input`, `input-label`, `primary-button`,
`secondary-button`, `danger-button`, `modal`) foram repintados para a mesma paleta (usados no perfil).

| Componente | Uso |
|---|---|
| `<x-ui.page-header title kicker>` | Título (Fraunces) + slots `description` e `actions` |
| `<x-ui.card title tone>` | Superfície (`tone`: default / income / expense) |
| `<x-ui.button variant size href>` | `primary` · `secondary` · `accent` (Editar) · `danger`; com `href` vira `<a>` |
| `<x-ui.field label for hint required>` | Rótulo + controle (`class="ui-input"`) + erro de validação |
| `<x-ui.confirm-delete action label>` | Exclusão em 2 cliques (não use `confirm()` nativo) |
| `<x-ui.flash />` | Mensagens `session('success')` / `session('error')` |

- **Não** adicione `<style>` nem `!important` em views. As classes legadas `.btn-*` agora têm
  uma definição única em `resources/css/app.css` (`@layer components`), na paleta de Finanças.
- **Não** monte classes Tailwind dinamicamente (`bg-{{ $cor }}-100`): o JIT não as detecta.
- `darkMode: 'class'` no Tailwind: o app não tem tema escuro; classes `dark:` legadas ficam inertes.

## Formatação monetária

**Regra:** todo valor monetário na UI passa por `App\Support\Money` (não por `Format` direto).

- `App\Support\Format` é **pura, sem estado** (`brl(float)`, `brlCompact(float)`, `brlSigned(float)`, `percent(float)`, `daysBetween(...)`).
  Use em services, jobs, e-mails.
- `App\Support\Money` é um **value object com estado** (`hidden: bool`). Use em **views e partials**
  porque respeita a preferência `session('hide_values')` e retorna `R$ •••••` quando oculto.
- View Composer injeta `$money` em todas as views de `layouts.*`, `finance.*`, `investment.*`, `admin.*`
  (ver `app/Providers/AppServiceProvider.php`).
- **Nunca** formate R$ com `number_format()` em view: ignora "ocultar valores" (coberto por
  `tests/Feature/HiddenValuesCoverageTest.php`). Cotações unitárias (4–6 casas) são exceção.

## Armadilhas conhecidas

- **Permissões**: `storage` e `bootstrap/cache` precisam ser do `www-data` no container (o `entrypoint.sh` ajusta).
- **Google OAuth em dev**: sem `GOOGLE_CLIENT_ID`/`SECRET` o callback cai em `/login` com erro; use o modo demonstração
  ou, nos testes, `actingAs()` / Socialite simulado (ver `tests/Feature/Auth/GoogleLoginTest.php`).
- **Saldos**: o saldo da conta é recalculado por `Account::updateBalance()` (o `TransactionService` chama). Ao inserir
  transações direto no banco (seeders), chame `updateBalance()` depois.
- **Posição de investimento** (`current_quantity`, `total_invested`, `current_value`, lucro) é calculada pelo
  `InvestmentPositionService` (custo médio, taxas no custo) a cada compra/venda e mudança de cotação. Quem grava
  lançamentos direto no banco (seeders) chama `recalculate()` depois; `php artisan investment:recalculate --dry-run`
  mostra o que mudaria. Ativo que nunca teve lançamento mantém a quantidade digitada (manual).
  Vendas maiores que a posição na data são recusadas pelo `StoreInvestmentTransactionRequest`.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
