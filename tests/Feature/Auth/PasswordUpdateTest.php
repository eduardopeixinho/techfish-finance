<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Troca de senha foi removida: login é exclusivo via Google.
 */
class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_cannot_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertNotFound();

        $this->assertFalse(Hash::check('new-password', $user->refresh()->password));
    }
}
