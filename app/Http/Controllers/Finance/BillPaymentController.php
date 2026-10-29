<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\MarkBillPaidRequest;
use App\Http\Requests\Finance\PayBillRequest;
use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use App\Services\BillPaymentService;
use App\Services\CreditCardBillService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

/**
 * Pagamentos de fatura de cartão: pagar (com transferência), marcar como paga (sem lançamento) e desfazer.
 */
class BillPaymentController extends Controller
{
    public function __construct(
        private CreditCardBillService $bills,
        private BillPaymentService $payments,
    ) {}

    public function store(PayBillRequest $request, Account $account, string $billIdentifier): RedirectResponse
    {
        $this->ensureBillExists($account, $billIdentifier);

        $from = Account::forUser($request->user()->id)->findOrFail($request->integer('from_account_id'));
        $this->payments->pay($account, $billIdentifier, $from, (float) $request->input('amount'), $request->input('paid_at'));

        return redirect()->route('finance.bills.show', ['account' => $account->id, 'billIdentifier' => $billIdentifier])
            ->with('success', 'Pagamento registrado.');
    }

    public function markPaid(MarkBillPaidRequest $request, Account $account, string $billIdentifier): RedirectResponse
    {
        $summary = $this->ensureBillExists($account, $billIdentifier);
        $showUrl = route('finance.bills.show', ['account' => $account->id, 'billIdentifier' => $billIdentifier]);

        if ($summary['remaining_amount'] <= 0) {
            return redirect($showUrl)->with('success', 'Esta fatura já está paga.');
        }

        // Data do "pagamento": o vencimento, se já passou; senão hoje
        $due = $summary['due_date'] ? Carbon::parse($summary['due_date']) : null;
        $paidAt = $due && $due->isPast() ? $due->toDateString() : Carbon::today()->toDateString();

        $this->payments->markPaid($account, $billIdentifier, $summary['remaining_amount'], $paidAt);

        return redirect($showUrl)->with('success', 'Fatura marcada como paga (sem lançar pagamento).');
    }

    public function destroy(BillPayment $billPayment): RedirectResponse
    {
        abort_unless($billPayment->user_id === auth()->id(), 404);

        $showUrl = route('finance.bills.show', ['account' => $billPayment->account_id, 'billIdentifier' => $billPayment->bill_identifier]);
        $this->payments->remove($billPayment);

        return redirect($showUrl)->with('success', 'Pagamento desfeito. A fatura voltou a ficar em aberto.');
    }

    /**
     * @return array<string, mixed>
     */
    private function ensureBillExists(Account $account, string $billIdentifier): array
    {
        $summary = $this->bills->getBillSummary($account, $billIdentifier);
        abort_if($summary['transaction_count'] === 0, 404);

        return $summary;
    }
}
