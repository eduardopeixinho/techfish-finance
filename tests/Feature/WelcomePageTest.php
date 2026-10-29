<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Página inicial pública.
 */
class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_landing_with_google_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Faturas de Cartão')
            ->assertSee('href="'.route('google.login').'"', false)
            ->assertDontSee('Ver demonstração');
    }

    public function test_demo_button_appears_when_demo_mode_is_on(): void
    {
        config(['demo.enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ver demonstração')
            ->assertSee('action="'.route('demo.login').'"', false);
    }

    public function test_logged_in_user_goes_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('finance.dashboard'));
    }

    /**
     * Instância só de demonstração (sem credenciais do Google): o botão do Google some e a rota não quebra.
     */
    public function test_google_button_is_hidden_when_not_configured(): void
    {
        config(['services.google.enabled' => false, 'demo.enabled' => true]);

        $this->get('/')->assertOk()
            ->assertDontSee('href="'.route('google.login').'"', false)
            ->assertSee('Ver demonstração');
        $this->get(route('login'))->assertOk()->assertDontSee('Entrar com Google');

        $this->get(route('google.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_demo_reset_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('db:seed')
            ->assertSuccessful();
    }
}
