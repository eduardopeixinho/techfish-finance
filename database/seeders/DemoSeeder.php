<?php

namespace Database\Seeders;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use App\Services\InvestmentPositionService;
use App\Services\OnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Conta de demonstração com dados fictícios (config/demo.php).
 *
 * Idempotente: apaga e recria os dados da conta demo a cada execução, então dá para
 * "resetar" a demonstração rodando de novo. Valores em R$, datas relativas a hoje
 * (últimos 6 meses), para os gráficos sempre terem conteúdo.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InvestmentTypeSeeder::class);

        DB::transaction(function (): void {
            $user = User::updateOrCreate(
                ['email' => config('demo.email')],
                // Senha aleatória: ninguém entra por senha (só pelo botão de demonstração)
                ['name' => config('demo.name'), 'nickname' => 'Demo', 'email_verified_at' => now(), 'password' => Str::random(64)],
            );

            $this->resetData($user);

            $categories = $this->seedCategories($user);
            $accounts = $this->seedAccounts($user);
            $this->seedTransactions($user, $accounts, $categories);
            $this->seedInvestments($user);

            foreach ($accounts as $account) {
                $account->updateBalance();
            }
        });
    }

    /**
     * Apaga de verdade (forceDelete) os dados da conta demo, inclusive os já excluídos:
     * com soft delete, o reset diário deixaria linhas mortas acumulando no banco.
     */
    private function resetData(User $user): void
    {
        InvestmentDividend::withTrashed()->where('user_id', $user->id)->forceDelete();
        InvestmentTransaction::withTrashed()->where('user_id', $user->id)->forceDelete();
        Investment::withTrashed()->where('user_id', $user->id)->forceDelete();
        Transaction::withTrashed()->where('user_id', $user->id)->forceDelete();
        Account::withTrashed()->where('user_id', $user->id)->forceDelete();
        FinancialCategory::where('user_id', $user->id)->delete(); // sem soft delete
    }

    /**
     * @return array<string, FinancialCategory>
     */
    private function seedCategories(User $user): array
    {
        // Mesmas categorias sugeridas aos usuários novos (primeiros passos)
        $categories = [];
        foreach (OnboardingService::DEFAULT_CATEGORIES as $name => [$type, $color]) {
            $categories[$name] = FinancialCategory::create([
                'user_id' => $user->id, 'name' => $name, 'type' => $type, 'color' => $color, 'is_active' => true,
            ]);
        }

        return $categories;
    }

    /**
     * @return array<string, Account>
     */
    private function seedAccounts(User $user): array
    {
        $definitions = [
            'corrente' => ['Conta Corrente', 'Banco Exemplo', 'checking', 3500, '#0B3D3A'],
            'poupanca' => ['Reserva de Emergência', 'Banco Exemplo', 'savings', 12000, '#2F855A'],
            'cartao' => ['Cartão de Crédito', 'Banco Exemplo', 'credit_card', 0, '#B8956A'],
        ];

        $accounts = [];
        foreach ($definitions as $key => [$name, $bank, $type, $initialBalance, $color]) {
            $accounts[$key] = Account::create([
                'user_id' => $user->id, 'name' => $name, 'bank' => $bank, 'type' => $type,
                'initial_balance' => $initialBalance, 'current_balance' => $initialBalance,
                'currency' => 'BRL', 'color' => $color, 'is_active' => true,
            ]);
        }

        return $accounts;
    }

    /**
     * Seis meses de movimento: salário e contas fixas na conta corrente,
     * compras no cartão (com fatura por competência), um aporte mensal na reserva e
     * o pagamento de cada fatura já vencida (transferência da corrente para o cartão),
     * para o saldo do cartão mostrar só o que está em aberto.
     *
     * @param  array<string, Account>  $accounts
     * @param  array<string, FinancialCategory>  $categories
     */
    private function seedTransactions(User $user, array $accounts, array $categories): void
    {
        $add = function (array $data) use ($user): void {
            Transaction::create($data + ['user_id' => $user->id, 'status' => 'completed']);
        };

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $month = Carbon::today()->startOfMonth()->subMonths($monthsAgo);
            $day = fn (int $day): Carbon => $month->copy()->day(min($day, $month->daysInMonth));
            $variation = 1 + (($monthsAgo % 3) - 1) * 0.08;

            $add(['description' => 'Salário', 'amount' => 7200, 'type' => 'income', 'transaction_date' => $day(5),
                'account_id' => $accounts['corrente']->id, 'category_id' => $categories['Salário']->id, 'payment_method' => 'bank_transfer']);

            if ($monthsAgo % 2 === 0) {
                $add(['description' => 'Projeto freelance', 'amount' => round(1500 * $variation, 2), 'type' => 'income', 'transaction_date' => $day(18),
                    'account_id' => $accounts['corrente']->id, 'category_id' => $categories['Freelance']->id, 'payment_method' => 'pix']);
            }

            $add(['description' => 'Aluguel', 'amount' => 2100, 'type' => 'expense', 'transaction_date' => $day(10),
                'account_id' => $accounts['corrente']->id, 'category_id' => $categories['Moradia']->id, 'payment_method' => 'bank_transfer']);
            $add(['description' => 'Energia e internet', 'amount' => round(320 * $variation, 2), 'type' => 'expense', 'transaction_date' => $day(12),
                'account_id' => $accounts['corrente']->id, 'category_id' => $categories['Moradia']->id, 'payment_method' => 'pix']);
            $add(['description' => 'Aporte na reserva', 'amount' => 800, 'type' => 'transfer', 'transaction_date' => $day(6),
                'account_id' => $accounts['corrente']->id, 'transfer_account_id' => $accounts['poupanca']->id, 'payment_method' => 'bank_transfer']);

            // Cartão: fatura identificada por "MM/AAAA" (competência) e vencimento no dia 10 do mês seguinte
            $card = [
                'account_id' => $accounts['cartao']->id, 'payment_method' => 'credit_card',
                'competency_date' => $month->toDateString(), 'bill_identifier' => $month->format('m/Y'),
                'due_date' => $month->copy()->addMonth()->day(10)->toDateString(),
            ];
            $purchases = [
                ['Supermercado', round(980 * $variation, 2), 8, 'Mercado'],
                ['Combustível', round(420 * $variation, 2), 14, 'Transporte'],
                ['Farmácia', round(160 * $variation, 2), 17, 'Saúde'],
                ['Restaurante', round(260 * $variation, 2), 21, 'Lazer'],
                ['Streaming e apps', 89.90, 3, 'Assinaturas'],
            ];
            foreach ($purchases as [$description, $amount, $purchaseDay, $category]) {
                $add($card + ['description' => $description, 'amount' => $amount, 'type' => 'expense',
                    'transaction_date' => $day($purchaseDay), 'category_id' => $categories[$category]->id]);
            }

            // Fatura já vencida: paga no vencimento com transferência da corrente para o cartão (R$)
            $dueDate = Carbon::parse($card['due_date']);
            if ($dueDate->lte(Carbon::today())) {
                $add(['description' => 'Pagamento da fatura '.$card['bill_identifier'], 'type' => 'transfer',
                    'amount' => round(array_sum(array_column($purchases, 1)), 2), 'transaction_date' => $dueDate->toDateString(),
                    'account_id' => $accounts['corrente']->id, 'transfer_account_id' => $accounts['cartao']->id,
                    'payment_method' => 'bank_transfer']);
            }
        }
    }

    /**
     * Carteira fictícia: compras ao longo dos meses e proventos mensais dos FIIs.
     * A posição (quantidade, custo médio, valor atual) sai do InvestmentPositionService.
     */
    private function seedInvestments(User $user): void
    {
        $types = InvestmentType::pluck('id', 'code');

        $assets = [
            // ticker, nome, tipo, preço médio, preço atual, quantidade, provento mensal por cota
            ['EXMP11', 'Fundo Imobiliário Exemplo', 'fii', 98.50, 101.20, 120, 0.85],
            ['DEMO11', 'FII Demonstração Logística', 'fii', 110.00, 106.40, 80, 0.92],
            ['EXPL3', 'Empresa Exemplo ON', 'stock', 32.40, 36.10, 200, null],
            ['TSTE4', 'Companhia Teste PN', 'stock', 18.75, 17.90, 300, null],
        ];

        foreach ($assets as [$ticker, $name, $typeCode, $averagePrice, $currentPrice, $quantity, $monthlyIncome]) {
            $investment = Investment::create([
                'user_id' => $user->id,
                'investment_type_id' => $types[$typeCode] ?? $types->first(),
                'ticker' => $ticker,
                'name' => $name,
                'current_price' => $currentPrice,
                'current_quantity' => 0,
                'is_active' => true,
            ]);

            // Duas compras (metade há 5 meses, metade há 2) que somam a posição atual
            foreach ([5, 2] as $monthsAgo) {
                $lot = intdiv($quantity, 2);
                InvestmentTransaction::create([
                    'user_id' => $user->id, 'investment_id' => $investment->id, 'type' => 'buy',
                    'quantity' => $lot, 'price' => $averagePrice,
                    'total_amount' => round($lot * $averagePrice, 2), 'fees' => 0, 'net_amount' => round($lot * $averagePrice, 2),
                    'transaction_date' => Carbon::today()->subMonths($monthsAgo)->toDateString(),
                ]);
            }

            app(InvestmentPositionService::class)->recalculate($investment);

            if ($monthlyIncome) {
                for ($monthsAgo = 4; $monthsAgo >= 0; $monthsAgo--) {
                    $owned = $monthsAgo >= 2 ? intdiv($quantity, 2) : $quantity;
                    $amount = round($owned * $monthlyIncome, 2);
                    $paymentDate = Carbon::today()->startOfMonth()->subMonths($monthsAgo)->day(15);
                    InvestmentDividend::create([
                        'user_id' => $user->id, 'investment_id' => $investment->id, 'type' => 'rent',
                        'amount' => $amount, 'amount_per_share' => $monthlyIncome, 'quantity_owned' => $owned,
                        'record_date' => $paymentDate->copy()->subDays(10)->toDateString(),
                        'payment_date' => $paymentDate->toDateString(),
                        'tax_withheld' => 0, 'net_amount' => $amount,
                    ]);
                }
            }
        }
    }
}
