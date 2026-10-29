<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_accounts(): void
    {
        $user = User::factory()->create();
        Account::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('finance.accounts.index'));

        $response->assertOk();
        $response->assertViewHas('accounts');
    }

    public function test_user_cannot_see_other_users_accounts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Account::factory()->create(['user_id' => $other->id, 'name' => 'Conta Secreta']);

        $response = $this->actingAs($user)->get(route('finance.accounts.index'));

        $response->assertOk();
        $response->assertDontSee('Conta Secreta');
    }

    public function test_user_can_create_an_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('finance.accounts.store'), [
            'name' => 'Minha Conta',
            'type' => 'checking',
            'initial_balance' => 1000.00,
            'currency' => 'BRL',
            'color' => '#007bff',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('finance.accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Minha Conta',
            'initial_balance' => 1000.00,
            'current_balance' => 1000.00,
        ]);
    }

    public function test_user_can_update_their_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'Antigo Nome']);

        $response = $this->actingAs($user)->put(route('finance.accounts.update', $account), [
            'name' => 'Novo Nome',
            'type' => 'checking',
            'initial_balance' => $account->initial_balance,
            'currency' => $account->currency,
            'color' => '#28a745',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('finance.accounts.index'));
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Novo Nome']);
    }

    public function test_user_cannot_update_another_users_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->put(route('finance.accounts.update', $account), [
            'name' => 'Hackeado',
            'type' => 'checking',
            'initial_balance' => $account->initial_balance,
            'currency' => $account->currency,
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('finance.accounts.destroy', $account));

        $response->assertRedirect(route('finance.accounts.index'));
        $this->assertSoftDeleted($account);
    }

    /**
     * Regressão: checkbox desmarcado não era enviado e a conta nunca era desativada.
     */
    public function test_unchecking_active_deactivates_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'is_active' => true]);

        $this->actingAs($user)->get(route('finance.accounts.edit', $account))
            ->assertOk()
            ->assertSee('<input type="hidden" name="is_active" value="0">', false);

        // O navegador envia só o hidden (0) quando o checkbox está desmarcado
        $this->actingAs($user)->put(route('finance.accounts.update', $account), [
            'name' => $account->name,
            'type' => 'checking',
            'initial_balance' => 100,
            'currency' => 'BRL',
            'color' => '#0B3D3A',
            'is_active' => '0',
        ])->assertRedirect();

        $this->assertFalse($account->fresh()->is_active);
    }

    public function test_account_pages_show_type_label_in_portuguese(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'type' => 'credit_card', 'name' => 'Cartão Roxo']);

        $this->actingAs($user)->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('Cartão de crédito')
            ->assertDontSee('credit card');

        $this->actingAs($user)->get(route('finance.accounts.show', $account))
            ->assertOk()
            ->assertSee('Cartão de crédito');

        $this->actingAs($user)->get(route('finance.accounts.create'))
            ->assertOk()
            ->assertSee('<option value="other"', false);
    }
}
