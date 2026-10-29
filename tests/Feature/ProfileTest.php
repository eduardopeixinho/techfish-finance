<?php

namespace Tests\Feature;

use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/edit');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/edit');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_edit_page_renders_without_password_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile/edit')
            ->assertOk()
            ->assertSee('Excluir conta')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_user_can_delete_their_account_by_typing_their_email(): void
    {
        $user = User::factory()->create(['email' => 'fulano@gmail.com']);
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'email_confirmation' => '  Fulano@Gmail.com ',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        // Dados financeiros caem junto (FK cascade)
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_wrong_email_does_not_delete_account(): void
    {
        $user = User::factory()->create(['email' => 'fulano@gmail.com']);

        $response = $this
            ->actingAs($user)
            ->from('/profile/edit')
            ->delete('/profile', [
                'email_confirmation' => 'outro@gmail.com',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'email_confirmation')
            ->assertRedirect('/profile/edit');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }

    public function test_empty_confirmation_does_not_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/profile/edit')
            ->delete('/profile', [])
            ->assertSessionHasErrorsIn('userDeletion', 'email_confirmation');

        $this->assertNotNull($user->fresh());
    }
}
