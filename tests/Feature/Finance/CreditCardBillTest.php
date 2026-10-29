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
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 200,
            'bill_identifier' => '07/2025',
            'competency_date' => Carbon::parse('2025-07-01'),
        ]);

        $bills = $this->billService->getAvailableBills($account);

        $this->assertCount(2, $bills);
        $this->assertEquals('07/2025', $bills->first()['bill_identifier']);
        $this->assertEquals('06/2025', $bills->last()['bill_identifier']);
    }

    public function test_create_bill_for_date(): void
    {
        $competencyDate = Carbon::parse('2025-06-15');

        $bill = $this->billService->createBillForDate($competencyDate, 10);

        $this->assertEquals('06/2025', $bill['bill_identifier']);
        $this->assertEquals('2025-06-01', $bill['competency_date']->format('Y-m-d'));
        $this->assertEquals('2025-07-10', $bill['due_date']->format('Y-m-d'));
    }

    public function test_determine_competency_for_transaction_before_cutoff(): void
    {
        $transactionDate = Carbon::parse('2025-06-03'); // Antes do dia 5

        $bill = $this->billService->determineCompetencyForTransaction($transactionDate, 5);

        $this->assertEquals('06/2025', $bill['bill_identifier']);
    }

    public function test_determine_competency_for_transaction_after_cutoff(): void
    {
        $transactionDate = Carbon::parse('2025-06-10'); // Depois do dia 5

        $bill = $this->billService->determineCompetencyForTransaction($transactionDate, 5);

        $this->assertEquals('07/2025', $bill['bill_identifier']);
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
     * bill_identifier tem barra ("06/2025"): a rota precisa aceitar o segmento com "/".
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
            ->assertSee('Fatura de Junho de 2025')
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
            ['07/2025', '2025-07-01', '2025-08-10'], // em dia
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
        $this->assertSame(1, substr_count($html, '>Em dia<'));
    }
}
