<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use Illuminate\Support\Facades\DB;

/**
 * Pagamento de faturas de cartão (valores em R$).
 *
 * - pay(): registra a transferência da conta de origem para o cartão (forma "Boleto") e liga à fatura.
 *   O saldo da conta de origem cai e a dívida do cartão diminui.
 * - markPaid(): só marca a fatura como paga, sem lançamento nem mudança de saldo (histórico não lançado).
 * - remove(): desfaz; se havia transferência, ela é excluída (e os saldos, recalculados).
 */
class BillPaymentService
{
    public function __construct(
        private TransactionService $transactions,
    ) {}

    public function pay(Account $card, string $billIdentifier, Account $from, float $amount, string $paidAt): BillPayment
    {
        return DB::transaction(function () use ($card, $billIdentifier, $from, $amount, $paidAt): BillPayment {
            $transaction = $this->transactions->create([
                'description' => "Pagamento da fatura {$billIdentifier} · {$card->name}",
                'amount' => round($amount, 2),
                'type' => 'transfer',
                'transaction_date' => $paidAt,
                'account_id' => $from->id,
                'transfer_account_id' => $card->id,
                'payment_method' => 'Boleto',
            ]);

            return BillPayment::create([
                'user_id' => $card->user_id,
                'account_id' => $card->id,
                'bill_identifier' => $billIdentifier,
                'transaction_id' => $transaction->id,
                'amount' => round($amount, 2),
                'paid_at' => $paidAt,
            ]);
        });
    }

    public function markPaid(Account $card, string $billIdentifier, float $amount, string $paidAt): BillPayment
    {
        return BillPayment::create([
            'user_id' => $card->user_id,
            'account_id' => $card->id,
            'bill_identifier' => $billIdentifier,
            'transaction_id' => null,
            'amount' => round($amount, 2),
            'paid_at' => $paidAt,
        ]);
    }

    public function remove(BillPayment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            // TransactionService::delete também apaga o BillPayment ligado à transferência
            $payment->transaction
                ? $this->transactions->delete($payment->transaction)
                : $payment->delete();
        });
    }
}
