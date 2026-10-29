<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Confirmação de senha foi removida: usuários do Google não têm senha conhecida.
 */
class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_is_not_available(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/confirm-password')->assertNotFound();
        $this->actingAs($user)->post('/confirm-password', ['password' => 'password'])->assertNotFound();
    }
}
