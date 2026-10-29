<?php

namespace Tests\Feature\Integration;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o fluxo financeiro completo:
     * 1. Admin cria tipos de conta, tipos de renda e métodos de pagamento
     * 2. Usuário cria conta
     * 3. Usuário cria categorias
     * 4. Usuário adiciona transações
     * 5. Usuário visualiza dashboard e relatórios
     */
    public function test_complete_finance_flow(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Configurações iniciais (simulando admin)
        $accountType = AccountType::factory()->create(['name' => 'Conta Corrente']);
        $incomeType = IncomeType::factory()->create(['name' => 'Salário']);
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'PIX']);

        // Act & Assert - Passo 1: Usuário cria conta
        $response = $this->actingAs($user)->post(route('finance.accounts.store'), [
            'name' => 'Nubank',
            'type' => 'checking',
            'initial_balance' => 1000.00,
            'currency' => 'BRL',
            'color' => '#8A2BE2',
            'description' => 'Conta Nubank',
        ]);

        $response->assertRedirect(route('finance.accounts.index'));

        $account = Account::where('name', 'Nubank')->first();
        $this->assertNotNull($account);
        $this->assertEquals($user->id, $account->user_id);
        $this->assertEquals(1000.00, $account->current_balance);

        // Passo 2: Usuário cria categorias
        $expenseCategory = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Alimentação',
            'type' => 'expense',
        ]);

        $incomeCategory = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Salário',
            'type' => 'income',
        ]);

        // Passo 3: Usuário adiciona transação de receita
        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Salário Janeiro',
            'amount' => 5000.00,
            'type' => 'income',
            'transaction_date' => now()->format('Y-m-d'),
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'payment_method' => 'pix',
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        $account->refresh();
        $this->assertEquals(6000.00, $account->current_balance); // 1000 + 5000

        // Passo 4: Usuário adiciona transação de despesa
        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Supermercado',
            'amount' => 350.00,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'payment_method' => 'pix',
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        $account->refresh();
        $this->assertEquals(5650.00, $account->current_balance); // 6000 - 350

        // Passo 5: Usuário visualiza dashboard
        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalBalance', 5650.00);
        $response->assertViewHas('monthlyIncome', 5000.00);
        $response->assertViewHas('monthlyExpenses', 350.00);
        $response->assertViewHas('monthlyBalance', 4650.00);

        // Passo 6: Usuário visualiza relatórios
        $response = $this->actingAs($user)->get(route('finance.reports'));

        $response->assertStatus(200);
        $response->assertViewHas('incomeReport');
        $response->assertViewHas('expenseReport');
        $response->assertViewHas('dailyEvolution');
        $response->assertViewHas('accountReport');
    }

    /**
     * Testa o fluxo de transferência entre contas
     */
    public function test_account_transfer_flow(): void
    {
        $user = User::factory()->create();

        // Cria duas contas
        $account1 = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente',
            'initial_balance' => 5000.00,
            'current_balance' => 5000.00,
        ]);

        $account2 = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Poupança',
            'initial_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        $category = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Transferência',
            'type' => 'transfer',
        ]);

        // Realiza transferência de R$ 2000 da conta 1 para conta 2
        $response = $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Transferência para Poupança',
            'amount' => 2000.00,
            'type' => 'transfer',
            'transaction_date' => now()->format('Y-m-d'),
            'account_id' => $account1->id,
            'transfer_account_id' => $account2->id,
            'category_id' => $category->id,
            'payment_method' => 'transfer',
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('finance.transactions.index'));

        $account1->refresh();
        $account2->refresh();

        $this->assertEquals(3000.00, $account1->current_balance); // 5000 - 2000
        $this->assertEquals(2000.00, $account2->current_balance); // 0 + 2000
    }

    /**
     * Testa o fluxo de fatura de cartão de crédito
     */
    public function test_credit_card_bill_flow(): void
    {
        $user = User::factory()->create();

        // Cria conta de cartão de crédito
        $creditCard = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Cartão Nubank',
            'type' => 'credit_card',
            'initial_balance' => 0.00,
            'current_balance' => 0.00,
        ]);

        $category = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'name' => 'Restaurante',
            'type' => 'expense',
        ]);

        // Adiciona compras no cartão com competência
        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Restaurante Italiano',
            'amount' => 150.00,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
            'account_id' => $creditCard->id,
            'category_id' => $category->id,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'bill_identifier' => now()->format('m/Y'),
            'competency_date' => now()->startOfMonth()->format('Y-m-d'),
            'due_date' => now()->addMonth()->day(10)->format('Y-m-d'),
        ]);

        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Pizzaria',
            'amount' => 80.00,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
            'account_id' => $creditCard->id,
            'category_id' => $category->id,
            'payment_method' => 'credit_card',
            'status' => 'completed',
            'bill_identifier' => now()->format('m/Y'),
            'competency_date' => now()->startOfMonth()->format('Y-m-d'),
            'due_date' => now()->addMonth()->day(10)->format('Y-m-d'),
        ]);

        // Visualiza faturas
        $response = $this->actingAs($user)->get(route('finance.bills.index'));
        $response->assertStatus(200);
    }
}
