<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use App\Models\Finance\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fluxo da fatura de cartão: compra cai na fatura certa, total com estornos, pagar, marcar como paga e desfazer.
 * Cartão fecha dia 1 e vence dia 8; fatura "MM-AAAA" = mês do vencimento. Valores em R$.
 */
class BillFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $card;

    private Account $checking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2025-09-20 10:00'));

        $this->user = User::factory()->create();
        $this->card = Account::factory()->create([
            'user_id' => $this->user->id, 'type' => 'credit_card', 'name' => 'Cartão Teste',
            'closing_day' => 1, 'due_day' => 8, 'initial_balance' => 0, 'current_balance' => 0,
        ]);
        $this->checking = Account::factory()->create([
            'user_id' => $this->user->id, 'type' => 'checking', 'name' => 'Corrente',
            'initial_balance' => 5000, 'current_balance' => 5000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function buy(float $amount, string $date, array $overrides = []): void
    {
        $this->actingAs($this->user)->post(route('finance.transactions.store'), array_merge([
            'description' => 'Compra', 'amount' => $amount, 'type' => 'expense',
            'transaction_date' => $date, 'account_id' => $this->card->id,
        ], $overrides))->assertSessionHasNoErrors();
    }

    private function billUrl(string $identifier): string
    {
        return route('finance.bills.show', ['account' => $this->card->id, 'billIdentifier' => $identifier]);
    }

    public function test_card_purchase_from_the_form_goes_to_the_right_bill(): void
    {
        $this->buy(100, '2025-08-15');
        $this->buy(50, '2025-09-01'); // no dia do fechamento: próxima fatura

        $this->assertDatabaseHas('transactions', ['amount' => 100, 'bill_identifier' => '09-2025', 'due_date' => '2025-09-08 00:00:00']);
        $this->assertDatabaseHas('transactions', ['amount' => 50, 'bill_identifier' => '10-2025']);

        $this->actingAs($this->user)->get(route('finance.bills.index'))
            ->assertOk()
            ->assertSee('Setembro de 2025')
            ->assertSee('Outubro de 2025');
    }

    public function test_bill_can_be_chosen_in_the_form(): void
    {
        $this->buy(80, '2025-08-15', ['bill_identifier' => '11-2025']);

        $this->assertDatabaseHas('transactions', ['amount' => 80, 'bill_identifier' => '11-2025', 'due_date' => '2025-11-08 00:00:00']);
    }

    public function test_transfers_and_other_accounts_have_no_bill(): void
    {
        $this->actingAs($this->user)->post(route('finance.transactions.store'), [
            'description' => 'Mercado', 'amount' => 30, 'type' => 'expense',
            'transaction_date' => '2025-08-15', 'account_id' => $this->checking->id, 'bill_identifier' => '09-2025',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('transactions', ['description' => 'Mercado', 'bill_identifier' => null, 'due_date' => null]);
    }

    public function test_refund_reduces_the_bill_total(): void
    {
        $this->buy(300, '2025-08-10');
        $this->buy(120, '2025-08-12', ['type' => 'income', 'description' => 'Estorno']);

        $this->actingAs($this->user)->get($this->billUrl('09-2025'))
            ->assertOk()
            ->assertSee('R$ 180,00')
            ->assertSee('+ R$ 120,00');
    }

    /**
     * Parcela importada (data da compra original de 2023 numa fatura de 2025): editar não muda a fatura.
     */
    public function test_editing_without_changing_date_keeps_the_bill(): void
    {
        $installment = Transaction::factory()->create([
            'user_id' => $this->user->id, 'account_id' => $this->card->id, 'type' => 'expense', 'amount' => 99,
            'transaction_date' => '2023-11-11', 'bill_identifier' => '09-2025',
            'competency_date' => '2025-08-01', 'due_date' => '2025-09-08',
        ]);

        $this->actingAs($this->user)->put(route('finance.transactions.update', $installment), [
            'description' => 'Parcela 22/24', 'amount' => 99, 'type' => 'expense',
            'transaction_date' => '2023-11-11', 'account_id' => $this->card->id, 'bill_identifier' => '09-2025',
        ])->assertSessionHasNoErrors();

        $installment->refresh();
        $this->assertSame('09-2025', $installment->bill_identifier);
        $this->assertSame('2025-08-01', $installment->competency_date->toDateString());

        // Sem a fatura no formulário e com outra data: recalcula
        $this->actingAs($this->user)->put(route('finance.transactions.update', $installment), [
            'description' => 'Parcela 22/24', 'amount' => 99, 'type' => 'expense',
            'transaction_date' => '2025-09-15', 'account_id' => $this->card->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame('10-2025', $installment->fresh()->bill_identifier);
    }

    public function test_card_requires_billing_days_and_other_accounts_drop_them(): void
    {
        $base = ['name' => 'Novo', 'initial_balance' => 0, 'currency' => 'BRL', 'color' => '#123456'];

        $this->actingAs($this->user)->post(route('finance.accounts.store'), $base + ['type' => 'credit_card'])
            ->assertSessionHasErrors(['closing_day', 'due_day']);

        $this->actingAs($this->user)->post(route('finance.accounts.store'), $base + ['type' => 'checking', 'closing_day' => 5, 'due_day' => 12])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('accounts', ['name' => 'Novo', 'type' => 'checking', 'closing_day' => null, 'due_day' => null]);
    }

    public function test_paying_a_bill_records_a_boleto_transfer_and_marks_it_paid(): void
    {
        $this->buy(400, '2025-08-15');

        $this->actingAs($this->user)->post(route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']), [
            'from_account_id' => $this->checking->id, 'amount' => 400, 'paid_at' => '2025-09-08',
        ])->assertRedirect($this->billUrl('09-2025'));

        $this->assertDatabaseHas('transactions', [
            'type' => 'transfer', 'account_id' => $this->checking->id, 'transfer_account_id' => $this->card->id,
            'amount' => 400, 'payment_method' => 'Boleto',
        ]);
        $this->assertEquals(4600.00, (float) $this->checking->fresh()->current_balance);
        $this->assertEquals(0.0, (float) $this->card->fresh()->current_balance);

        $this->actingAs($this->user)->get($this->billUrl('09-2025'))
            ->assertSee('>Paga</span>', false)
            ->assertSee('pago com Corrente');
    }

    public function test_partial_payment_and_overpayment(): void
    {
        $this->buy(400, '2025-08-15');
        $payUrl = route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']);

        $this->actingAs($this->user)->post($payUrl, ['from_account_id' => $this->checking->id, 'amount' => 150, 'paid_at' => '2025-09-08'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->user)->get($this->billUrl('09-2025'))->assertSee('>Paga em parte</span>', false)->assertSee('R$ 250,00');

        $this->actingAs($this->user)->post($payUrl, ['from_account_id' => $this->checking->id, 'amount' => 251, 'paid_at' => '2025-09-08'])
            ->assertSessionHasErrors('amount');
        $this->assertSame(1, BillPayment::count());
    }

    public function test_payment_must_come_from_own_non_card_account(): void
    {
        $this->buy(100, '2025-08-15');
        $payUrl = route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']);
        $foreign = Account::factory()->create(['type' => 'checking']);

        $this->actingAs($this->user)->post($payUrl, ['from_account_id' => $foreign->id, 'amount' => 100, 'paid_at' => '2025-09-08'])
            ->assertSessionHasErrors('from_account_id');
        $this->actingAs($this->user)->post($payUrl, ['from_account_id' => $this->card->id, 'amount' => 100, 'paid_at' => '2025-09-08'])
            ->assertSessionHasErrors('from_account_id');

        $this->assertSame(0, BillPayment::count());
    }

    public function test_mark_as_paid_changes_only_the_status(): void
    {
        $this->buy(400, '2025-08-15');

        $this->actingAs($this->user)->post(route('finance.bills.mark-paid', ['account' => $this->card->id, 'billIdentifier' => '09-2025']))
            ->assertRedirect($this->billUrl('09-2025'));

        $this->assertDatabaseHas('bill_payments', ['bill_identifier' => '09-2025', 'transaction_id' => null, 'amount' => 400, 'paid_at' => '2025-09-08 00:00:00']);
        $this->assertSame(0, Transaction::where('type', 'transfer')->count());
        $this->assertEquals(5000.00, (float) $this->checking->fresh()->current_balance);
        $this->actingAs($this->user)->get($this->billUrl('09-2025'))->assertSee('marcada como paga (sem lançamento)');
    }

    public function test_undoing_payments_reopens_the_bill(): void
    {
        $this->buy(400, '2025-08-15');
        $this->actingAs($this->user)->post(route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']), [
            'from_account_id' => $this->checking->id, 'amount' => 400, 'paid_at' => '2025-09-08',
        ]);
        $payment = BillPayment::firstOrFail();

        $this->actingAs($this->user)->delete(route('finance.bills.payments.destroy', $payment))->assertRedirect($this->billUrl('09-2025'));

        $this->assertSame(0, BillPayment::count());
        $this->assertSame(0, Transaction::where('type', 'transfer')->count());
        $this->assertEquals(5000.00, (float) $this->checking->fresh()->current_balance);
    }

    public function test_deleting_or_editing_the_payment_transfer_updates_the_bill(): void
    {
        $this->buy(400, '2025-08-15');
        $this->actingAs($this->user)->post(route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']), [
            'from_account_id' => $this->checking->id, 'amount' => 400, 'paid_at' => '2025-09-08',
        ]);
        $transfer = Transaction::where('type', 'transfer')->firstOrFail();

        // Editou o valor da transferência: o pagamento acompanha
        $this->actingAs($this->user)->put(route('finance.transactions.update', $transfer), [
            'description' => $transfer->description, 'amount' => 300, 'type' => 'transfer', 'transaction_date' => '2025-09-08',
            'account_id' => $this->checking->id, 'transfer_account_id' => $this->card->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(300.00, (float) BillPayment::firstOrFail()->amount);

        // Excluiu a transferência: a fatura volta a ficar em aberto
        $this->actingAs($this->user)->delete(route('finance.transactions.destroy', $transfer));
        $this->assertSame(0, BillPayment::count());
    }

    public function test_other_user_cannot_pay_or_undo(): void
    {
        $this->buy(100, '2025-08-15');
        $intruder = User::factory()->create();
        $intruderChecking = Account::factory()->create(['user_id' => $intruder->id, 'type' => 'checking']);

        $this->actingAs($intruder)->post(route('finance.bills.payments.store', ['account' => $this->card->id, 'billIdentifier' => '09-2025']), [
            'from_account_id' => $intruderChecking->id, 'amount' => 100, 'paid_at' => '2025-09-08',
        ])->assertForbidden();
        $this->actingAs($intruder)->post(route('finance.bills.mark-paid', ['account' => $this->card->id, 'billIdentifier' => '09-2025']))
            ->assertForbidden();

        $payment = BillPayment::factory()->create(['user_id' => $this->user->id, 'account_id' => $this->card->id, 'bill_identifier' => '09-2025']);
        $this->actingAs($intruder)->delete(route('finance.bills.payments.destroy', $payment))->assertNotFound();
        $this->assertModelExists($payment);
    }

    public function test_paying_a_bill_that_does_not_exist_is_404(): void
    {
        $this->actingAs($this->user)->post(route('finance.bills.mark-paid', ['account' => $this->card->id, 'billIdentifier' => '01-2020']))
            ->assertNotFound();
    }

    public function test_hidden_values_do_not_prefill_the_amount(): void
    {
        $this->buy(400, '2025-08-15');

        $this->actingAs($this->user)->withSession(['hide_values' => true])->get($this->billUrl('09-2025'))
            ->assertOk()
            ->assertDontSee('value="400.00"', false);
    }

    public function test_mark_paid_command_dry_run_then_apply(): void
    {
        $this->buy(400, '2025-07-15'); // fatura 08-2025 (vencida)
        $this->buy(100, '2025-09-10'); // fatura 10-2025 (futura)

        $this->artisan('bills:mark-paid', ['--until' => '2025-08-31', '--dry-run' => true])
            ->expectsOutputToContain('08-2025')
            ->expectsOutputToContain('Nada foi gravado')
            ->assertSuccessful();
        $this->assertSame(0, BillPayment::count());

        $this->artisan('bills:mark-paid', ['--until' => '2025-08-31'])->assertSuccessful();
        $this->assertDatabaseHas('bill_payments', ['bill_identifier' => '08-2025', 'amount' => 400, 'transaction_id' => null]);
        $this->assertDatabaseMissing('bill_payments', ['bill_identifier' => '10-2025']);

        $this->artisan('bills:mark-paid', ['--until' => '2025-08-31'])->expectsOutputToContain('Nenhuma fatura em aberto')->assertSuccessful();
    }
}
