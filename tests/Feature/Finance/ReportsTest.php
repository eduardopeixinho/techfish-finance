<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\FinanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_reports(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('finance.reports'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.reports');
        $response->assertViewHas('incomeReport');
        $response->assertViewHas('expenseReport');
        $response->assertViewHas('dailyEvolution');
        $response->assertViewHas('accountReport');
    }

    public function test_reports_with_date_filter(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        // Criar transação em data específica
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 150,
            'transaction_date' => '2025-06-15',
        ]);

        $response = $this->actingAs($user)->get(route('finance.reports', [
            'start_date' => '2025-06-01',
            'end_date' => '2025-06-30',
        ]));

        $response->assertStatus(200);
        $expenseReport = $response->viewData('expenseReport');
        $this->assertNotEmpty($expenseReport);
    }

    public function test_reports_shows_category_breakdown(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $category1 = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'type' => 'expense',
            'name' => 'Alimentação',
        ]);
        $category2 = FinancialCategory::factory()->create([
            'user_id' => $user->id,
            'type' => 'expense',
            'name' => 'Transporte',
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category1->id,
            'type' => 'expense',
            'amount' => 200,
            'transaction_date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category2->id,
            'type' => 'expense',
            'amount' => 100,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('finance.reports'));

        $response->assertStatus(200);
        $expenseReport = $response->viewData('expenseReport');
        $this->assertCount(2, $expenseReport);
    }

    public function test_reports_shows_daily_evolution(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        // Criar transações em dias diferentes
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50,
            'transaction_date' => now()->startOfMonth(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 75,
            'transaction_date' => now()->startOfMonth()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('finance.reports'));

        $response->assertStatus(200);
        $dailyEvolution = $response->viewData('dailyEvolution');
        $this->assertNotEmpty($dailyEvolution);
    }

    public function test_reports_shows_account_breakdown(): void
    {
        $user = User::factory()->create();
        $account1 = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Conta Corrente',
            'initial_balance' => 1000,
            'current_balance' => 1000,
        ]);
        $account2 = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Poupança',
            'initial_balance' => 500,
            'current_balance' => 500,
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account1->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100,
            'transaction_date' => now(),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account2->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50,
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('finance.reports'));

        $response->assertStatus(200);
        $accountReport = $response->viewData('accountReport');
        $this->assertCount(2, $accountReport);
    }

    public function test_unauthenticated_user_cannot_view_reports(): void
    {
        $response = $this->get(route('finance.reports'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_see_other_users_data_in_reports(): void
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
            'transaction_date' => now(),
        ]);

        $response = $this->actingAs($user1)->get(route('finance.reports'));

        $response->assertStatus(200);
        $expenseReport = $response->viewData('expenseReport');
        $this->assertEmpty($expenseReport);
    }

    /**
     * Regressão: o relatório ficava em cache por 5 min e não refletia lançamentos novos.
     */
    public function test_reports_reflect_new_transaction_immediately(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        $before = $this->actingAs($user)->get(route('finance.reports'))->viewData('expenseReport');
        $this->assertEmpty($before);

        $this->actingAs($user)->post(route('finance.transactions.store'), [
            'description' => 'Mercado',
            'amount' => 123.45,
            'type' => 'expense',
            'transaction_date' => now()->toDateString(),
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])->assertRedirect();

        $after = $this->actingAs($user)->get(route('finance.reports'))->viewData('expenseReport');
        $this->assertNotEmpty($after);
    }

    public function test_report_cache_is_per_user(): void
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $keyUserOne = FinanceReportService::reportCacheKey(1, $start, $end);

        FinanceReportService::forgetUserReports(2);

        $this->assertSame($keyUserOne, FinanceReportService::reportCacheKey(1, $start, $end));
        $this->assertNotSame($keyUserOne, FinanceReportService::reportCacheKey(2, $start, $end));
    }

    public function test_reports_page_offers_csv_for_selected_period_and_no_fake_pdf(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('finance.reports', ['start_date' => '2025-06-01', 'end_date' => '2025-06-30']))
            ->assertOk()
            // assertSee escapa a string: bate com o href (& vira &amp;)
            ->assertSee(route('finance.reports.export-csv', ['start_date' => '2025-06-01', 'end_date' => '2025-06-30']))
            ->assertSee('01/06/2025 a 30/06/2025')
            ->assertDontSee('Exportar PDF')
            ->assertDontSee('alert(', false);
    }

    public function test_monthly_evolution_does_not_skip_months_on_the_31st(): void
    {
        $this->travelTo(Carbon::parse('2026-03-31 12:00'));
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense',
            'amount' => 40, 'transaction_date' => '2026-02-10',
        ]);

        $evolution = app(FinanceReportService::class)->getMonthlyEvolution($user->id, 6);

        $this->assertCount(6, $evolution);
        // Penúltimo = fevereiro (antes, 31/03 - 1 mês caía em março de novo e fevereiro sumia)
        $this->assertSame('Fev/2026', $evolution[4]['month']);
        $this->assertSame(40.0, $evolution[4]['expense']);
    }
}
