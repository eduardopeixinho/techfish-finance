<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.dashboard');
        $response->assertViewHas('totalBalance');
        $response->assertViewHas('monthlyIncome');
        $response->assertViewHas('monthlyExpenses');
        $response->assertViewHas('monthlyBalance');
        $response->assertViewHas('recentTransactions');
        $response->assertViewHas('accounts');
        $response->assertViewHas('expensesByCategory');
        $response->assertViewHas('monthlyEvolution');
    }

    public function test_dashboard_shows_correct_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 500,
            'current_balance' => 500,
        ]);

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals(500, $response->viewData('totalBalance'));
    }

    public function test_dashboard_shows_recent_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        // Criar 3 transações
        Transaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
        ]);

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $recentTransactions = $response->viewData('recentTransactions');
        $this->assertCount(3, $recentTransactions);
    }

    public function test_dashboard_shows_monthly_income_and_expenses(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $expenseCategory = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);
        $incomeCategory = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'income']);

        // Criar transações do mês atual
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'type' => 'expense',
            'amount' => 200,
            'transaction_date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'type' => 'income',
            'amount' => 500,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals(500, $response->viewData('monthlyIncome'));
        $this->assertEquals(200, $response->viewData('monthlyExpenses'));
        $this->assertEquals(300, $response->viewData('monthlyBalance'));
    }

    public function test_unauthenticated_user_cannot_view_dashboard(): void
    {
        $response = $this->get(route('finance.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_see_other_users_transactions_on_dashboard(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = Account::factory()->create([
            'user_id' => $user1->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $account2 = Account::factory()->create([
            'user_id' => $user2->id,
            'initial_balance' => 500,
            'current_balance' => 500,
        ]);

        $category = FinancialCategory::factory()->create(['user_id' => $user2->id, 'type' => 'expense']);

        // Criar transação para user2
        Transaction::factory()->create([
            'user_id' => $user2->id,
            'account_id' => $account2->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100,
        ]);

        $response = $this->actingAs($user1)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        $recentTransactions = $response->viewData('recentTransactions');
        $this->assertCount(0, $recentTransactions);
    }

    public function test_dashboard_renders_mask_when_hide_values_is_true(): void
    {
        $user = User::factory()->create();
        Account::factory()->create([
            'user_id' => $user->id,
            'current_balance' => 1000,
        ]);
        session(['hide_values' => true]);

        $response = $this->actingAs($user)->get(route('finance.dashboard'));

        $response->assertStatus(200);
        // Pelo menos um valor é mascarado
        $this->assertStringContainsString(Money::MASK, $response->getContent());
    }

    public function test_month_filter_shows_month_names_in_portuguese(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Setembro')
            ->assertSee('aria-label="Mês"', false)
            ->assertDontSee('>Sep<', false);
    }

    public function test_account_type_is_shown_in_portuguese(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id, 'type' => 'checking', 'is_active' => true]);

        $this->actingAs($user)->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Conta corrente')
            ->assertDontSee('>checking<', false);
    }
}
