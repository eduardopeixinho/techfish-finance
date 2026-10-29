<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login é exclusivo via Google: a tela /login só oferece o botão do Google
 * e o POST de e-mail/senha não existe mais.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_offers_only_google(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('href="'.route('google.login').'"', false);
        $response->assertDontSee('name="password"', false);
        // Ícone do Google é SVG inline (antes dependia do Bootstrap Icons, que não era carregado)
        $response->assertDontSee('bi bi-google', false);
        $response->assertSee('Entrar com Google');
    }

    public function test_login_screen_shows_oauth_error(): void
    {
        $this->withSession(['error' => 'Acesso permitido apenas para e-mails autorizados.'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Acesso permitido apenas para e-mails autorizados.');
    }

    public function test_password_login_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(405);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
