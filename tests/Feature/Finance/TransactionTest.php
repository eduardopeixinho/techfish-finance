<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_expense_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'initial_balance' => 1000, 'current_balance' => 1000]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Supermercado',
            'amount' => 150.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payment_method' => 'pix',
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'description' => 'Supermercado',
            'amount' => 150.00,
            'type' => 'expense',
            'user_id' => $user->id,
        ]);
    }

    public function test_creating_expense_updates_account_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Aluguel',
            'amount' => 500.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $account->id,
            'payment_method' => 'pix',
            'status' => 'completed',
        ]);

        $account->refresh();
        $this->assertEquals(500.00, $account->current_balance);
    }

    public function test_creating_income_updates_account_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'income']);

        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Salário',
            'amount' => 3000.00,
            'type' => 'income',
            'transaction_date' => '2025-06-01',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
        ]);

        $account->refresh();
        $this->assertEquals(3000.00, $account->current_balance);
    }

    public function test_user_cannot_create_transaction_for_another_users_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Compra',
            'amount' => 100.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $otherAccount->id,
            'status' => 'completed',
        ]);

        $response->assertForbidden();
    }

    /**
     * Regressão: o update aceitava account_id de outro usuário e movia a
     * transação para o extrato/saldo dele.
     */
    public function test_user_cannot_move_transaction_to_another_users_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $otherAccount = Account::factory()->create(['user_id' => User::factory()->create()->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense']);

        $this->actingAs($user)->put(route('finance.transactions.update', $transaction), [
            'description' => 'Compra',
            'amount' => 999.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $otherAccount->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'account_id' => $account->id]);
    }

    public function test_user_cannot_set_another_users_account_as_transfer_destination_on_update(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $otherAccount = Account::factory()->create(['user_id' => User::factory()->create()->id]);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense']);

        $this->actingAs($user)->put(route('finance.transactions.update', $transaction), [
            'description' => 'Transferência',
            'amount' => 50.00,
            'type' => 'transfer',
            'transaction_date' => '2025-06-01',
            'account_id' => $account->id,
            'transfer_account_id' => $otherAccount->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'transfer_account_id' => null]);
    }

    public function test_user_cannot_use_another_users_category(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $otherCategory = FinancialCategory::factory()->create(['type' => 'expense']);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense']);

        $payload = [
            'description' => 'Compra',
            'amount' => 10.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $account->id,
            'category_id' => $otherCategory->id,
        ];

        $this->actingAs($user)->post(route('finance.transactions.store'), $payload)->assertForbidden();
        $this->actingAs($user)->put(route('finance.transactions.update', $transaction), $payload)->assertForbidden();

        $this->assertDatabaseMissing('transactions', ['category_id' => $otherCategory->id]);
    }

    public function test_user_can_update_transaction_within_own_accounts(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $newAccount = Account::factory()->create(['user_id' => $user->id]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense']);

        $this->actingAs($user)->put(route('finance.transactions.update', $transaction), [
            'description' => 'Compra editada',
            'amount' => 42.00,
            'type' => 'expense',
            'transaction_date' => '2025-06-01',
            'account_id' => $newAccount->id,
            'category_id' => $category->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'account_id' => $newAccount->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_list_their_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->count(5)->create(['user_id' => $user->id, 'account_id' => $account->id]);

        $response = $this->actingAs($user)->get(route('finance.transactions.index'));

        $response->assertOk();
        $response->assertViewHas('transactions');
    }

    public function test_user_cannot_see_other_users_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherAccount = Account::factory()->create(['user_id' => $other->id]);
        Transaction::factory()->create([
            'user_id' => $other->id,
            'account_id' => $otherAccount->id,
            'description' => 'Despesa Secreta',
        ]);

        $response = $this->actingAs($user)->get(route('finance.transactions.index'));

        $response->assertOk();
        $response->assertDontSee('Despesa Secreta');
    }

    public function test_create_form_preselects_account_from_query_string(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id, 'name' => 'Outra']);
        $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'Nubank']);

        $this->actingAs($user)
            ->get(route('finance.transactions.create', ['account_id' => $account->id]))
            ->assertOk()
            ->assertSee('<option value="'.$account->id.'" selected', false);
    }

    /**
     * Regressão: o formulário de criação não usava old() e perdia tudo após erro de validação.
     */
    public function test_create_form_keeps_input_after_validation_error(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['_old_input' => ['description' => 'Padaria do bairro', 'amount' => '12.5', 'type' => 'income']])
            ->get(route('finance.transactions.create'))
            ->assertOk()
            ->assertSee('value="Padaria do bairro"', false)
            ->assertSee('value="12.5"', false)
            ->assertSee('type: \'income\'', false);
    }

    public function test_create_form_without_accounts_asks_to_create_one(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('finance.transactions.create'))
            ->assertOk()
            ->assertSee('crie antes uma conta')
            ->assertDontSee('name="amount"', false);
    }

    public function test_index_filters_and_uses_two_step_delete(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense', 'description' => 'Farmácia']);
        Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id, 'type' => 'income', 'description' => 'Salário']);

        $this->actingAs($user)
            ->get(route('finance.transactions.index', ['type' => 'income']))
            ->assertOk()
            ->assertSee('Salário')
            ->assertDontSee('Farmácia')
            ->assertSee('Confirmar exclusão', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_show_and_edit_pages_render(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'Conta Show']);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense',
            'description' => 'Conta de luz', 'payment_method' => 'pix',
        ]);

        $this->actingAs($user)->get(route('finance.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Conta de luz')
            ->assertSee('Despesa')
            ->assertSee('Conta Show');

        $this->actingAs($user)->get(route('finance.transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('value="Conta de luz"', false)
            ->assertDontSee('unpkg.com/alpinejs', false);
    }
}
