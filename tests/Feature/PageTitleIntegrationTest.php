<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O <title> dos layouts vem de App\Support\PageTitle: "Página — Nome do app".
 */
class PageTitleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_dashboard_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('<title>Painel — '.config('app.name').'</title>', false);
    }

    public function test_investment_dashboard_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('investment.dashboard'))
            ->assertOk()
            ->assertSee('<title>Investimentos — '.config('app.name').'</title>', false);
    }
}
