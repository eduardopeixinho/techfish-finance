<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Recuperação de senha foi removida: login é exclusivo via Google, não há senha a recuperar.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_screens_are_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->get('/reset-password/qualquer-token')->assertNotFound();
    }

    public function test_reset_password_link_cannot_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();

        Notification::assertNothingSent();
    }

    public function test_password_cannot_be_reset(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'qualquer-token',
            'email' => $user->email,
            'password' => 'nova-senha',
            'password_confirmation' => 'nova-senha',
        ])->assertNotFound();
    }
}
