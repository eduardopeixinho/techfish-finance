<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * Callback do Google OAuth: único caminho de login.
 */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $googleId = 'google-123'): void
    {
        $googleUser = (new SocialiteUser)->map([
            'id' => $googleId,
            'nickname' => null,
            'name' => 'Fulano de Tal',
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_first_login_creates_verified_user_and_authenticates(): void
    {
        $this->fakeGoogleUser('fulano@gmail.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $user = User::where('email', 'fulano@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_first_google_link_revokes_preexisting_password_and_remember_token(): void
    {
        // Conta criada por terceiro (antigo cadastro aberto) com o e-mail da vítima.
        $squatted = User::factory()->create([
            'email' => 'fulano@gmail.com',
            'google_id' => null,
            'password' => 'senha-do-atacante',
            'remember_token' => 'token-do-atacante',
        ]);

        $this->fakeGoogleUser('fulano@gmail.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $squatted->refresh();
        $this->assertSame('google-123', $squatted->google_id);
        $this->assertFalse(Hash::check('senha-do-atacante', $squatted->password));
        $this->assertNotSame('token-do-atacante', $squatted->remember_token);
    }

    public function test_returning_google_user_keeps_password_hash(): void
    {
        $user = User::factory()->create(['email' => 'fulano@gmail.com', 'google_id' => 'google-123']);
        $originalHash = $user->password;

        $this->fakeGoogleUser('fulano@gmail.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame($originalHash, $user->refresh()->password);
        $this->assertSame('Fulano de Tal', $user->name);
    }

    public function test_disallowed_domain_is_rejected_without_creating_user(): void
    {
        config(['services.google.allowed_domains' => ['empresa.com.br']]);
        $this->fakeGoogleUser('estranho@gmail.com');

        $this->get(route('google.callback'))
            ->assertRedirect('/login')
            ->assertSessionHas('error');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'estranho@gmail.com']);
    }

    public function test_allowed_domain_is_case_insensitive(): void
    {
        config(['services.google.allowed_domains' => ['empresa.com.br']]);
        $this->fakeGoogleUser('Fulano@Empresa.com.br');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_oauth_failure_redirects_to_login(): void
    {
        Socialite::shouldReceive('driver->user')->andThrow(new \Exception('state inválido'));

        $this->get(route('google.callback'))
            ->assertRedirect('/login')
            ->assertSessionHas('error');

        $this->assertGuest();
    }
}
