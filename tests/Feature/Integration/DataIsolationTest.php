<?php

namespace Tests\Feature\Integration;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Isolamento entre usuários: o app é multiusuário (qualquer conta Google entra),
 * então nenhum dado financeiro/de investimento de A pode aparecer para B —
 * nem por acesso direto (id na URL), nem em listagens, dashboards, relatórios,
 * exportações ou selects de formulário.
 *
 * Os dados de A usam marcadores únicos ("SECRETO-A") para varrer o HTML.
 */
class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const MARKER = 'SECRETO-A';

    private User $owner;

    private User $intruder;

    /** @var array<string, Model> */
    private array $ownerData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->intruder = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Conta '.self::MARKER,
            'type' => 'credit_card',
        ]);
        $category = FinancialCategory::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Categoria '.self::MARKER,
            'type' => 'expense',
        ]);
        $transaction = Transaction::factory()->create([
            'user_id' => $this->owner->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'description' => 'Lançamento '.self::MARKER,
            'transaction_date' => now()->toDateString(),
            'bill_identifier' => now()->format('Y-m'),
        ]);
        $investment = Investment::factory()->create([
            'user_id' => $this->owner->id,
            'name' => 'Investimento '.self::MARKER,
            'ticker' => 'SECA11',
        ]);
        $investmentTransaction = InvestmentTransaction::factory()->create([
            'user_id' => $this->owner->id,
            'investment_id' => $investment->id,
            'notes' => 'Nota '.self::MARKER,
            'transaction_date' => now()->toDateString(),
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $this->owner->id,
            'investment_id' => $investment->id,
            'notes' => 'Provento '.self::MARKER,
            'payment_date' => now()->toDateString(),
        ]);

        $this->ownerData = compact('account', 'category', 'transaction', 'investment', 'investmentTransaction', 'dividend');
    }

    /**
     * Páginas que o intruso pode abrir: nenhuma pode conter dados do dono.
     *
     * @return array<string, array{0: string, 1?: array<string, mixed>}>
     */
    public static function listingRoutes(): array
    {
        return [
            'finanças: painel' => ['finance.dashboard'],
            'finanças: relatórios' => ['finance.reports'],
            'finanças: transações' => ['finance.transactions.index'],
            'finanças: nova transação (select de contas/categorias)' => ['finance.transactions.create'],
            'finanças: contas' => ['finance.accounts.index'],
            'finanças: categorias' => ['finance.categories.index'],
            'finanças: faturas' => ['finance.bills.index'],
            'investimentos: painel' => ['investment.dashboard'],
            'investimentos: carteira' => ['investment.investments.index'],
            'investimentos: transações' => ['investment.transactions.index'],
            'investimentos: nova transação (select de ativos)' => ['investment.transactions.create'],
            'investimentos: proventos' => ['investment.dividends.index'],
            'investimentos: novo provento (select de ativos)' => ['investment.dividends.create'],
            'investimentos: relatórios' => ['investment.reports'],
            'investimentos: carteira detalhada' => ['investment.reports.portfolio'],
            'investimentos: relatório de proventos' => ['investment.reports.dividends'],
        ];
    }

    #[DataProvider('listingRoutes')]
    public function test_intruder_listings_do_not_leak_owner_data(string $routeName): void
    {
        $response = $this->actingAs($this->intruder)->get(route($routeName));

        $response->assertOk();
        $response->assertDontSee(self::MARKER, false);
        $response->assertDontSee('SECA11', false);
    }

    public function test_intruder_csv_export_does_not_leak_owner_data(): void
    {
        $response = $this->actingAs($this->intruder)->get(route('finance.reports.export-csv', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $this->assertStringNotContainsString(self::MARKER, $response->streamedContent());
    }

    public function test_intruder_create_forms_ignore_preselected_owner_investment(): void
    {
        $investmentId = $this->ownerData['investment']->id;

        foreach (['investment.transactions.create', 'investment.dividends.create'] as $routeName) {
            $this->actingAs($this->intruder)
                ->get(route($routeName, ['investment_id' => $investmentId]))
                ->assertDontSee(self::MARKER, false);
        }
    }

    /**
     * Acesso direto pelo id: leitura e edição precisam ser negadas.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function ownedResources(): array
    {
        return [
            'conta' => ['finance.accounts', 'account', 'account'],
            'categoria' => ['finance.categories', 'category', 'category'],
            'transação' => ['finance.transactions', 'transaction', 'transaction'],
            'investimento' => ['investment.investments', 'investment', 'investment'],
            'transação de investimento' => ['investment.transactions', 'transaction', 'investmentTransaction'],
            'provento' => ['investment.dividends', 'dividend', 'dividend'],
        ];
    }

    #[DataProvider('ownedResources')]
    public function test_intruder_cannot_view_or_edit_owner_resource(string $routePrefix, string $parameter, string $dataKey): void
    {
        $model = $this->ownerData[$dataKey];

        $this->actingAs($this->intruder)
            ->get(route($routePrefix.'.show', [$parameter => $model]))
            ->assertForbidden();

        $this->actingAs($this->intruder)
            ->get(route($routePrefix.'.edit', [$parameter => $model]))
            ->assertForbidden();
    }

    #[DataProvider('ownedResources')]
    public function test_intruder_cannot_update_or_delete_owner_resource(string $routePrefix, string $parameter, string $dataKey): void
    {
        $model = $this->ownerData[$dataKey];
        $snapshot = $model->fresh()->getAttributes();

        // Payload propositalmente incompleto: seja pela validação ou pela policy, nada pode mudar.
        $this->actingAs($this->intruder)
            ->put(route($routePrefix.'.update', [$parameter => $model]), ['name' => 'invadido', 'description' => 'invadido', 'notes' => 'invadido']);

        $response = $this->actingAs($this->intruder)
            ->delete(route($routePrefix.'.destroy', [$parameter => $model]));

        $response->assertForbidden();
        $this->assertSame($snapshot, $model->fresh()->getAttributes());
    }

    public function test_intruder_cannot_open_owner_credit_card_bill(): void
    {
        $account = $this->ownerData['account'];
        $billIdentifier = now()->format('Y-m');

        $this->actingAs($this->intruder)
            ->get(route('finance.bills.show', [$account, $billIdentifier]))
            ->assertForbidden();

        $this->actingAs($this->intruder)
            ->get(route('finance.bills.api.summary', [$account, $billIdentifier]))
            ->assertForbidden();
    }

    public function test_owner_still_sees_own_data(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.transactions.index'))
            ->assertOk()
            ->assertSee('Lançamento '.self::MARKER);

        $this->actingAs($this->owner)
            ->get(route('investment.investments.index'))
            ->assertOk()
            ->assertSee('Investimento '.self::MARKER);
    }
}
