<?php

namespace Tests\Feature;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Ocultar valores" precisa valer em todas as telas com patrimônio.
 * Regressão: 20 views formatavam R$ com number_format() e ignoravam a preferência.
 *
 * Todos os valores monetários usam R$ 98.765,43 para serem fáceis de achar no HTML.
 * Cotações unitárias (4–6 casas) não são patrimônio e ficam visíveis de propósito.
 */
class HiddenValuesCoverageTest extends TestCase
{
    use RefreshDatabase;

    private const AMOUNT = 98765.43;

    private const AMOUNT_TEXT = '98.765,43';

    private User $user;

    /** @var array<string, Model> */
    private array $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'initial_balance' => self::AMOUNT,
            'current_balance' => self::AMOUNT,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $this->user->id, 'type' => 'expense']);
        $transaction = Transaction::factory()->create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => self::AMOUNT,
        ]);
        $investment = Investment::factory()->create([
            'user_id' => $this->user->id,
            'total_invested' => self::AMOUNT,
            'current_value' => self::AMOUNT,
            'profit_loss' => self::AMOUNT,
        ]);
        $investmentTransaction = InvestmentTransaction::factory()->create([
            'user_id' => $this->user->id,
            'investment_id' => $investment->id,
            'total_amount' => self::AMOUNT,
            'net_amount' => self::AMOUNT,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $this->user->id,
            'investment_id' => $investment->id,
            'amount' => self::AMOUNT,
            'net_amount' => self::AMOUNT,
            'payment_date' => now()->toDateString(),
        ]);

        $this->data = compact('account', 'category', 'transaction', 'investment', 'investmentTransaction', 'dividend');
    }

    /**
     * @return array<string, array{0: string, 1?: array{0: string, 1: string}}>
     */
    public static function pagesWithMoney(): array
    {
        return [
            'contas' => ['finance.accounts.index'],
            'conta' => ['finance.accounts.show', ['account', 'account']],
            'editar conta' => ['finance.accounts.edit', ['account', 'account']],
            'transação' => ['finance.transactions.show', ['transaction', 'transaction']],
            'nova transação (saldos no select)' => ['finance.transactions.create'],
            'categoria' => ['finance.categories.show', ['category', 'category']],
            'investimentos: painel' => ['investment.dashboard'],
            'investimentos: carteira' => ['investment.investments.index'],
            'investimento' => ['investment.investments.show', ['investment', 'investment']],
            'editar investimento' => ['investment.investments.edit', ['investment', 'investment']],
            'investimentos: transações' => ['investment.transactions.index'],
            'transação de investimento' => ['investment.transactions.show', ['transaction', 'investmentTransaction']],
            'proventos' => ['investment.dividends.index'],
            'provento' => ['investment.dividends.show', ['dividend', 'dividend']],
            'relatório de proventos' => ['investment.reports.dividends'],
            'carteira detalhada' => ['investment.reports.portfolio'],
        ];
    }

    /**
     * @param  array{0: string, 1: string}|null  $binding  [parâmetro da rota, chave em $this->data]
     */
    #[DataProvider('pagesWithMoney')]
    public function test_hidden_values_mask_money(string $routeName, ?array $binding = null): void
    {
        $url = $binding ? route($routeName, [$binding[0] => $this->data[$binding[1]]]) : route($routeName);

        $this->actingAs($this->user)
            ->withSession(['hide_values' => true])
            ->get($url)
            ->assertOk()
            ->assertDontSee(self::AMOUNT_TEXT, false)
            ->assertSee(Money::MASK, false);
    }

    public function test_values_are_shown_when_not_hidden(): void
    {
        $this->actingAs($this->user)
            ->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee(self::AMOUNT_TEXT, false);
    }
}
