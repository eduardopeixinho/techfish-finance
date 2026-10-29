<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\CreditCardBillService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardBillTest extends TestCase
{
    use RefreshDatabase;

    protected CreditCardBillService $billService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billService = app(CreditCardBillService::class);
    }

    public function test_get_transactions_by_bill(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        $billIdentifier = '06/2025';

        Transaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'bill_identifier' => $billIdentifier,
            'competency_date' => Carbon::parse('2025-06-01'),
        ]);

        // Transação de outra fatura
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'bill_identifier' => '07/2025',
            'competency_date' => Carbon::parse('2025-07-01'),
        ]);

        $transactions = $this->billService->getTransactionsByBill($account, $billIdentifier);

        $this->assertCount(3, $transactions);
        $this->assertTrue($transactions->every(fn ($t) => $t->bill_identifier === $billIdentifier));
    }

    public function test_get_bill_summary(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        $billIdentifier = '06/2025';

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100,
            'bill_identifier' => $billIdentifier,
            'competency_date' => Carbon::parse('2025-06-01'),
            'due_date' => Carbon::parse('2025-07-10'),
            'transaction_date' => Carbon::parse('2025-06-05'),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 50,
            'bill_identifier' => $billIdentifier,
            'competency_date' => Carbon::parse('2025-06-01'),
            'due_date' => Carbon::parse('2025-07-10'),
            'transaction_date' => Carbon::parse('2025-06-15'),
        ]);

        $summary = $this->billService->getBillSummary($account, $billIdentifier);

        $this->assertEquals($billIdentifier, $summary['bill_identifier']);
        $this->assertEquals(150, $summary['total_amount']);
        $this->assertEquals(2, $summary['transaction_count']);
        $this->assertNotNull($summary['competency_date']);
        $this->assertNotNull($summary['due_date']);
    }

    public function test_get_bill_summary_with_empty_bill(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);

        $summary = $this->billService->getBillSummary($account, '06/2025');

        $this->assertEquals('06/2025', $summary['bill_identifier']);
        $this->assertEquals(0, $summary['total_amount']);
        $this->assertEquals(0, $summary['transaction_count']);
        $this->assertNull($summary['competency_date']);
    }

    public function test_get_available_bills(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => 'credit_card',
        ]);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense']);

        // Criar transações para 2 faturas diferentes
        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100,
            'bill_identifier' => '06/2025',
            'competency_date' => Carbon::parse('2025-06-01'),
            'due_date' => Carbon::parse('2025-06-08'),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 200,
            'bill_identifier' => '07/2025',
            'competency_date' => Carbon::parse('2025-07-01'),
            'due_date' => Carbon::parse('2025-07-08'),
        ]);

        $bills = $this->billService->getAvailableBills($account);

        $this->assertCount(2, $bills);
        $this->assertEquals('07/2025', $bills->first()['bill_identifier']);
        $this->assertEquals('06/2025', $bills->last()['bill_identifier']);
    }

    /**
     * Fecha dia 1, vence dia 8 (cartões do dono): a fatura é nomeada pelo mês do vencimento.
     */
    public function test_bill_for_closing_on_day_1_due_on_day_8(): void
    {
        $card = Account::factory()->make(['type' => 'credit_card', 'closing_day' => 1, 'due_day' => 8]);

        $august = $this->billService->billFor($card, Carbon::parse('2025-08-15'));
        $this->assertSame('09-2025', $august['bill_identifier']);
        $this->assertSame('2025-09-08', $august['due_date']);

        // No dia do fechamento já vai para a próxima fatura
        $this->assertSame('10-2025', $this->billService->billFor($card, Carbon::parse('2025-09-01'))['bill_identifier']);
        $this->assertSame('09-2025', $this->billService->billFor($card, Carbon::parse('2025-08-31'))['bill_identifier']);
    }

    /**
     * Fecha dia 25 e vence dia 5: o vencimento cai no mês seguinte ao fechamento.
     */
    public function test_bill_for_closing_after_due_day(): void
    {
        $card = Account::factory()->make(['type' => 'credit_card', 'closing_day' => 25, 'due_day' => 5]);

        $this->assertSame('09-2025', $this->billService->billFor($card, Carbon::parse('2025-08-24'))['bill_identifier']);
        $this->assertSame('10-2025', $this->billService->billFor($card, Carbon::parse('2025-08-25'))['bill_identifier']);
        $this->assertSame('2025-10-05', $this->billService->billFor($card, Carbon::parse('2025-08-25'))['due_date']);
    }

    /**
     * Dia 31 em mês curto vira o último dia do mês (sem pular para o mês seguinte).
     */
    public function test_bill_for_short_months_and_year_turn(): void
    {
        $card = Account::factory()->make(['type' => 'credit_card', 'closing_day' => 31, 'due_day' => 31]);

        $february = $this->billService->billFor($card, Carbon::parse('2026-02-10'));
        $this->assertSame('03-2026', $february['bill_identifier']);
        $this->assertSame('2026-03-31', $february['due_date']);

        $default = Account::factory()->make(['type' => 'credit_card', 'closing_day' => null, 'due_day' => null]);
        $this->assertSame('01-2026', $this->billService->billFor($default, Carbon::parse('2025-12-20'))['bill_identifier']);
        $this->assertSame('2026-01-10', $this->billService->billFor($default, Carbon::parse('2025-12-20'))['due_date']);
    }

    public function test_bill_summary_includes_category_breakdown(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create([
            'user_id' => $user->id,
            'type' => 'credit_card',
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

        $billIdentifier = '06/2025';

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category1->id,
            'type' => 'expense',
            'amount' => 100,
            'bill_identifier' => $billIdentifier,
            'competency_date' => Carbon::parse('2025-06-01'),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category2->id,
            'type' => 'expense',
            'amount' => 50,
            'bill_identifier' => $billIdentifier,
            'competency_date' => Carbon::parse('2025-06-01'),
        ]);

        $summary = $this->billService->getBillSummary($account, $billIdentifier);

        $this->assertCount(2, $summary['categories']);
        $this->assertEquals('Alimentação', $summary['categories'][0]['category_name']);
        $this->assertEquals(100, $summary['categories'][0]['total_amount']);
    }

    public function test_user_cannot_see_other_users_bills(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = Account::factory()->create([
            'user_id' => $user1->id,
            'type' => 'credit_card',
        ]);

        $account2 = Account::factory()->create([
            'user_id' => $user2->id,
            'type' => 'credit_card',
        ]);

        $category = FinancialCategory::factory()->create(['user_id' => $user2->id, 'type' => 'expense']);

        // Criar transação para user2
        Transaction::factory()->create([
            'user_id' => $user2->id,
            'account_id' => $account2->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 100,
            'bill_identifier' => '06/2025',
            'competency_date' => Carbon::parse('2025-06-01'),
        ]);

        // user1 não deve ver as faturas de user2
        $bills = $this->billService->getAvailableBills($account1);
        $this->assertCount(0, $bills);

        $transactions = $this->billService->getTransactionsByBill($account1, '06/2025');
        $this->assertCount(0, $transactions);
    }

    /**
     * Dados antigos com barra ("06/2025") continuam abrindo; o título usa o mês do vencimento.
     */
    public function test_bill_page_opens_from_index_link_and_handles_uncategorized(): void
    {
        $user = User::factory()->create();
        $card = Account::factory()->create(['user_id' => $user->id, 'type' => 'credit_card', 'name' => 'Cartão Roxo']);
        $category = FinancialCategory::factory()->create(['user_id' => $user->id, 'type' => 'expense', 'name' => 'Mercado']);
        foreach ([[$category->id, 'Compra categorizada'], [null, 'Compra sem categoria']] as [$categoryId, $description]) {
            Transaction::factory()->create([
                'user_id' => $user->id, 'account_id' => $card->id, 'category_id' => $categoryId, 'type' => 'expense',
                'description' => $description, 'amount' => 50,
                'bill_identifier' => '06/2025', 'competency_date' => Carbon::parse('2025-06-01'), 'due_date' => Carbon::parse('2025-07-10'),
            ]);
        }

        $index = $this->actingAs($user)->get(route('finance.bills.index'))->assertOk();
        $url = route('finance.bills.show', ['account' => $card->id, 'billIdentifier' => '06/2025']);
        $index->assertSee($url, false);

        $this->actingAs($user)->get($url)
            ->assertOk()
            ->assertSee('Fatura de Julho de 2025')
            ->assertSee('Compra sem categoria')
            ->assertSee('Sem categoria')
            ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false);
    }

    public function test_bill_status_uses_days_until_due_date(): void
    {
        $this->travelTo(Carbon::parse('2025-07-01 15:00'));
        $user = User::factory()->create();
        $card = Account::factory()->create(['user_id' => $user->id, 'type' => 'credit_card']);
        $bills = [
            ['05/2025', '2025-05-01', '2025-06-10'], // vencida
            ['06/2025', '2025-06-01', '2025-07-05'], // vence em 4 dias
            ['07/2025', '2025-07-01', '2025-08-10'], // em aberto
            ['08/2025', '2025-08-01', '2025-07-01'], // vence hoje: ainda não vencida
        ];
        foreach ($bills as [$identifier, $competency, $due]) {
            Transaction::factory()->create([
                'user_id' => $user->id, 'account_id' => $card->id, 'type' => 'expense',
                'bill_identifier' => $identifier, 'competency_date' => $competency, 'due_date' => $due,
            ]);
        }

        $html = $this->actingAs($user)->get(route('finance.bills.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '>Vencida<'));
        $this->assertSame(2, substr_count($html, '>Vence em breve<'));
        $this->assertSame(1, substr_count($html, '>Em aberto</span>')); // selo ("Em aberto" também é título de coluna)
    }
}
