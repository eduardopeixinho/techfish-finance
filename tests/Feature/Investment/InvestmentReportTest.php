<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use App\Services\InvestmentReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Relatórios de investimentos (investment.reports e investment.reports.portfolio).
 * Antes as views não existiam e as duas rotas davam 500.
 */
class InvestmentReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $fii = InvestmentType::factory()->create(['name' => 'FII']);
        $stock = InvestmentType::factory()->create(['name' => 'Ação']);

        $hglg = Investment::factory()->create([
            'user_id' => $this->user->id,
            'investment_type_id' => $fii->id,
            'ticker' => 'HGLG11',
            'name' => 'CSHG Logística',
            'current_quantity' => 10,
            'total_invested' => 1000,
            'current_value' => 1100,
            'is_active' => true,
        ]);
        Investment::factory()->create([
            'user_id' => $this->user->id,
            'investment_type_id' => $stock->id,
            'ticker' => 'PETR4',
            'total_invested' => 500,
            'current_value' => 400,
            'is_active' => true,
        ]);
        Investment::factory()->create([
            'user_id' => $this->user->id,
            'investment_type_id' => $stock->id,
            'ticker' => 'VENDIDO3',
            'is_active' => false,
        ]);

        // R$ 50 líquidos neste mês; R$ 30 há 13 meses (fora da janela de 12 meses).
        InvestmentDividend::factory()->create([
            'user_id' => $this->user->id,
            'investment_id' => $hglg->id,
            'amount' => 50,
            'tax_withheld' => 0,
            'net_amount' => 50,
            'payment_date' => now()->startOfMonth()->addDays(4),
        ]);
        InvestmentDividend::factory()->create([
            'user_id' => $this->user->id,
            'investment_id' => $hglg->id,
            'amount' => 30,
            'tax_withheld' => 0,
            'net_amount' => 30,
            'payment_date' => now()->startOfMonth()->subMonths(13),
        ]);
    }

    public function test_reports_page_shows_allocation_and_dividends(): void
    {
        $response = $this->actingAs($this->user)->get(route('investment.reports'));

        $response->assertOk()
            ->assertSee('Alocação por tipo')
            ->assertSee('FII')
            ->assertSee('Ação')
            // Só ativos: investido 1.000 + 500; atual 1.100 + 400 (VENDIDO3, inativo, fica de fora)
            ->assertSeeInOrder(['Total investido', 'R$ 1.500,00', 'Valor atual', 'R$ 1.500,00'])
            // FII = 1.100 / 1.500 da carteira atual
            ->assertSee('73,3%')
            ->assertSee('Proventos por mês')
            ->assertSee('R$ 50,00');
    }

    public function test_portfolio_lists_only_active_assets_with_yield_on_cost(): void
    {
        $response = $this->actingAs($this->user)->get(route('investment.reports.portfolio'));

        $response->assertOk()
            ->assertSee('HGLG11')
            ->assertSee('PETR4')
            ->assertDontSee('VENDIDO3')
            ->assertSee('R$ 1.100,00')
            // Yield on cost do HGLG11: todos os proventos (50 + 30) / 1.000 = 8,0%
            ->assertSee('8,0%')
            ->assertSee('Total (2 ativos)');
    }

    public function test_reports_render_empty_state_for_new_user(): void
    {
        $newUser = User::factory()->create();

        $this->actingAs($newUser)->get(route('investment.reports'))
            ->assertOk()
            ->assertSee('Nenhum investimento cadastrado ainda');

        $this->actingAs($newUser)->get(route('investment.reports.portfolio'))
            ->assertOk()
            ->assertSee('Nenhum ativo na carteira');
    }

    public function test_reports_respect_hidden_values(): void
    {
        $this->actingAs($this->user)
            ->withSession(['hide_values' => true])
            ->get(route('investment.reports.portfolio'))
            ->assertOk()
            ->assertDontSee('R$ 1.100,00')
            ->assertSee('HGLG11');
    }

    public function test_dashboard_links_to_reports(): void
    {
        $this->actingAs($this->user)
            ->get(route('investment.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('investment.reports').'"', false);
    }

    public function test_monthly_evolution_does_not_skip_months_on_the_31st(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 31));

        InvestmentDividend::factory()->create([
            'user_id' => $this->user->id,
            'investment_id' => Investment::where('ticker', 'HGLG11')->value('id'),
            'amount' => 20,
            'tax_withheld' => 0,
            'net_amount' => 20,
            'payment_date' => '2026-02-10',
        ]);

        $evolution = app(InvestmentReportService::class)->getDividendMonthlyEvolution($this->user->id, 12);

        $this->assertCount(12, $evolution);
        // Penúltimo item = fevereiro/2026 (antes, 31/03 - 1 mês caía em março de novo)
        $this->assertSame(20.0, $evolution[10]['net']);
        $this->assertSame(0.0, $evolution[11]['net']);
    }
}
