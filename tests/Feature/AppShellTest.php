<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

/**
 * Casca do app: rota padrão pós-login, cabeçalho e menu principal.
 */
class AppShellTest extends TestCase
{
    use HasAdminUser;
    use RefreshDatabase;

    public function test_dashboard_route_redirects_to_finance_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertRedirect('/finance/dashboard');
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('finance.dashboard'))->assertRedirect(route('login'));
    }

    public function test_main_nav_marks_current_section(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('finance.transactions.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/aria-current="page"[^>]*>\s*Transações/', $html);
        foreach (['Painel', 'Contas', 'Faturas', 'Investimentos', 'Relatórios'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        $this->actingAs($user)->get(route('investment.investments.index'))
            ->assertOk()
            ->assertSeeInOrder(['aria-current="page"', 'Investimentos'], false);
    }

    public function test_admin_link_only_for_admins(): void
    {
        $this->actingAs($this->createRegularUser())
            ->get(route('finance.dashboard'))
            ->assertDontSee('href="'.route('admin.user-roles.index').'"', false);

        $this->actingAs($this->createAdminUser())
            ->get(route('finance.dashboard'))
            ->assertSee('href="'.route('admin.user-roles.index').'"', false);
    }

    public function test_profile_uses_app_header(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.view'))
            ->assertOk()
            ->assertSee('aria-label="Menu principal"', false);
    }

    /**
     * Configurações são catálogos globais (gate admin.super): o link não pode levar quem não é admin a um 403.
     */
    public function test_settings_links_only_for_admins(): void
    {
        $this->actingAs($this->createRegularUser())
            ->get(route('finance.dashboard'))
            ->assertDontSee('href="'.route('settings.index').'"', false);

        $this->actingAs($this->createAdminUser())
            ->get(route('finance.dashboard'))
            ->assertSee('href="'.route('settings.index').'"', false);
    }
}
