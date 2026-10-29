<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre a feature "ocultar valores" ponta a ponta:
 *  - PreferenceController alterna o estado na sessão
 *  - Dashboard renderiza com $money respeitando a preferência
 *  - Botão <x-value-toggle /> aparece no cabeçalho do app
 *  - Toggle preserva `back()` (volta para a página de origem)
 */
class ValueVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_toggle_values(): void
    {
        $response = $this->post(route('preferences.toggle-values'));

        $response->assertRedirect(route('login'));
    }

    public function test_toggle_values_flips_session_state(): void
    {
        $user = User::factory()->create();

        // Estado inicial: visível
        $this->assertFalse((bool) session('hide_values', false));

        $response = $this->actingAs($user)->post(route('preferences.toggle-values'));

        $response->assertStatus(302);
        $this->assertTrue(session('hide_values') === true);

        // Toggle de novo: volta a false
        $this->actingAs($user)->post(route('preferences.toggle-values'));
        $this->assertFalse((bool) session('hide_values', false));
    }

    public function test_toggle_values_redirects_back(): void
    {
        $user = User::factory()->create();

        // Vem do dashboard
        $response = $this->actingAs($user)
            ->from(route('finance.dashboard'))
            ->post(route('preferences.toggle-values'));

        $response->assertRedirect(route('finance.dashboard'));
    }

    public function test_dashboard_renders_values_when_visible(): void
    {
        $user = User::factory()->create();

        // Por padrão, hide_values é false
        $this->assertFalse((bool) session('hide_values', false));

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee(Money::MASK, false);
    }

    public function test_toggle_button_present_in_finance_context(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'action="'.route('preferences.toggle-values').'"',
            $response->getContent(),
            'Botão de toggle deve estar presente em Finanças'
        );
    }

    public function test_toggle_button_aria_pressed_reflects_state(): void
    {
        $user = User::factory()->create();

        // Estado inicial: aria-pressed=false
        $response = $this->actingAs($user)->get(route('finance.dashboard'));
        $response->assertSee('aria-pressed="false"', false);

        // Após toggle: aria-pressed=true
        session(['hide_values' => true]);
        $response = $this->actingAs($user)->get(route('finance.dashboard'));
        $response->assertSee('aria-pressed="true"', false);
    }
}
