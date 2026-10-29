<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Regras de negócio para transações financeiras.
 *
 * Centraliza a lógica de criação, atualização e exclusão de transações,
 * incluindo verificação de ownership, atualização de saldos e transferências.
 * Compra/estorno em cartão de crédito recebe a fatura (CreditCardBillService::billFor).
 */
class TransactionService
{
    public function __construct(private CreditCardBillService $bills) {}

    /**
     * Cria uma transação e atualiza os saldos das contas envolvidas.
     *
     * @param  array<string, mixed>  $data  Dados validados (sem user_id)
     *
     * @throws HttpException
     */
    public function create(array $data): Transaction
    {
        $account = $this->validateReferencesOwnership($data);
        $data = $this->withBill($data, $account);

        return DB::transaction(function () use ($data, $account) {
            $data['user_id'] = Auth::id();

            $transaction = Transaction::create($data);

            $account->updateBalance();

            if ($data['type'] === 'transfer' && isset($data['transfer_account_id'])) {
                $transferAccount = Account::forUser(Auth::id())->find($data['transfer_account_id']);
                if ($transferAccount) {
                    $transferAccount->updateBalance();
                }
            }

            FinanceReportService::forgetUserReports(Auth::id());

            return $transaction;
        });
    }

    /**
     * Atualiza uma transação e recalcula os saldos das contas afetadas.
     *
     * @param  array<string, mixed>  $data  Dados validados
     *
     * @throws HttpException
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        // Sem isso, dava para mover a transação para a conta/categoria de outro usuário.
        $account = $this->validateReferencesOwnership($data);
        $data = $this->withBill($data, $account, $transaction);

        return DB::transaction(function () use ($transaction, $data) {
            $oldAccountId = $transaction->account_id;
            $oldTransferAccountId = $transaction->transfer_account_id;

            $transaction->update($data);
            $this->syncBillPayments($transaction);

            // Atualizar saldos das contas afetadas (nova e antiga)
            $accountIds = collect([
                $oldAccountId,
                $data['account_id'] ?? null,
                $oldTransferAccountId,
                $data['transfer_account_id'] ?? null,
            ])
                ->filter()
                ->unique();

            foreach ($accountIds as $accountId) {
                $account = Account::forUser(Auth::id())->find($accountId);
                if ($account) {
                    $account->updateBalance();
                }
            }

            FinanceReportService::forgetUserReports(Auth::id());

            return $transaction;
        });
    }

    /**
     * Exclui uma transação e atualiza os saldos das contas envolvidas.
     */
    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $accountId = $transaction->account_id;
            $transferAccountId = $transaction->transfer_account_id;

            // Era o pagamento de uma fatura: a fatura volta a ficar em aberto
            BillPayment::where('transaction_id', $transaction->id)->delete();
            $transaction->delete();

            $account = Account::forUser(Auth::id())->find($accountId);
            if ($account) {
                $account->updateBalance();
            }

            if ($transferAccountId) {
                $transferAccount = Account::forUser(Auth::id())->find($transferAccountId);
                if ($transferAccount) {
                    $transferAccount->updateBalance();
                }
            }

            FinanceReportService::forgetUserReports(Auth::id());
        });
    }

    /**
     * Preenche (ou limpa) a fatura do lançamento.
     *
     * - Compra/estorno em cartão: a fatura escolhida no formulário (bill_identifier) ou a calculada pela data.
     * - Edição que não muda conta nem data mantém a fatura atual (ex.: parcela importada com a data da compra original).
     * - Qualquer outro lançamento fica sem fatura.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withBill(array $data, Account $account, ?Transaction $existing = null): array
    {
        $isCardPurchase = $account->isCreditCard() && in_array($data['type'] ?? null, ['expense', 'income'], true);

        if (! $isCardPurchase) {
            return array_merge($data, ['bill_identifier' => null, 'competency_date' => null, 'due_date' => null]);
        }

        $sameCard = $existing?->bill_identifier && (int) $existing->account_id === $account->id;

        // Mesma fatura que já estava (o formulário de edição vem com ela selecionada): não recalcula o vencimento
        if ($sameCard && ($data['bill_identifier'] ?? null) === $existing->bill_identifier) {
            unset($data['bill_identifier']);

            return $data;
        }

        if (! empty($data['bill_identifier'])) {
            return array_merge($data, $this->bills->billFromIdentifier($account, $data['bill_identifier']));
        }

        $keepsBill = $sameCard
            && $existing->transaction_date?->toDateString() === Carbon::parse($data['transaction_date'])->toDateString();

        if ($keepsBill) {
            unset($data['bill_identifier']);

            return $data;
        }

        return array_merge($data, $this->bills->billFor($account, $data['transaction_date']));
    }

    /**
     * Transferência que paga uma fatura foi editada: acompanha valor e data; se deixou de ir
     * para aquele cartão, deixa de contar como pagamento.
     */
    private function syncBillPayments(Transaction $transaction): void
    {
        foreach (BillPayment::where('transaction_id', $transaction->id)->get() as $payment) {
            $stillPaysCard = $transaction->type === 'transfer'
                && (int) $transaction->transfer_account_id === (int) $payment->account_id;

            $stillPaysCard
                ? $payment->update(['amount' => $transaction->amount, 'paid_at' => $transaction->transaction_date])
                : $payment->delete();
        }
    }

    /**
     * Valida que conta, conta de destino e categoria referenciadas pertencem ao usuário.
     * O `exists:` dos Form Requests só garante que o id existe, não o dono.
     *
     * @param  array<string, mixed>  $data
     * @return Account conta principal
     *
     * @throws HttpException
     */
    private function validateReferencesOwnership(array $data): Account
    {
        $account = $this->validateAccountOwnership((int) $data['account_id']);

        if (! empty($data['transfer_account_id'])) {
            $this->validateAccountOwnership((int) $data['transfer_account_id']);
        }

        if (! empty($data['category_id'])
            && ! FinancialCategory::forUser(Auth::id())->whereKey($data['category_id'])->exists()) {
            abort(403, "Voc\u00ea n\u00e3o tem permiss\u00e3o para usar esta categoria.");
        }

        return $account;
    }

    /**
     * Valida que a conta pertence ao usuário autenticado.
     *
     *
     * @throws HttpException
     */
    private function validateAccountOwnership(int $accountId): Account
    {
        $account = Account::forUser(Auth::id())->find($accountId);

        if (! $account) {
            abort(403, "Voc\u00ea n\u00e3o tem permiss\u00e3o para usar esta conta.");
        }

        return $account;
    }
}
