<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Regras de negócio para transações financeiras.
 *
 * Centraliza a lógica de criação, atualização e exclusão de transações,
 * incluindo verificação de ownership, atualização de saldos e transferências.
 */
class TransactionService
{
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
        $this->validateReferencesOwnership($data);

        return DB::transaction(function () use ($transaction, $data) {
            $oldAccountId = $transaction->account_id;
            $oldTransferAccountId = $transaction->transfer_account_id;

            $transaction->update($data);

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
