<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CreditCardBillService
{
    /**
     * Obter transações por competência de fatura (com categoria eager loaded)
     */
    public function getTransactionsByBill(Account $account, string $billIdentifier): Collection
    {
        return Transaction::with('category')
            ->where('user_id', $account->user_id)
            ->where('account_id', $account->id)
            ->where('bill_identifier', $billIdentifier)
            ->orderBy('transaction_date')
            ->get();
    }

    /**
     * Obter resumo da fatura por competência
     */
    public function getBillSummary(Account $account, string $billIdentifier): array
    {
        $transactions = $this->getTransactionsByBill($account, $billIdentifier);

        if ($transactions->isEmpty()) {
            return [
                'bill_identifier' => $billIdentifier,
                'total_amount' => 0,
                'transaction_count' => 0,
                'competency_date' => null,
                'due_date' => null,
                'categories' => [],
                'earliest_transaction' => null,
                'latest_transaction' => null,
            ];
        }

        $firstTransaction = $transactions->first();

        return [
            'bill_identifier' => $billIdentifier,
            'total_amount' => abs($transactions->sum('amount')),
            'transaction_count' => $transactions->count(),
            'competency_date' => $firstTransaction->competency_date,
            'due_date' => $firstTransaction->due_date,
            'categories' => $this->getCategoryBreakdown($transactions),
            'earliest_transaction' => $transactions->min('transaction_date'),
            'latest_transaction' => $transactions->max('transaction_date'),
        ];
    }

    /**
     * Obter detalhamento por categoria
     */
    private function getCategoryBreakdown(Collection $transactions): array
    {
        return $transactions
            ->groupBy(function ($transaction) {
                return $transaction->category->name ?? 'Sem categoria';
            })
            ->map(function ($categoryTransactions, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'total_amount' => abs($categoryTransactions->sum('amount')),
                    'transaction_count' => $categoryTransactions->count(),
                    'percentage' => 0, // Será calculado depois
                ];
            })
            ->sortByDesc('total_amount')
            ->values()
            ->toArray();
    }

    /**
     * Obter todas as competências disponíveis para uma conta
     * OTIMIZADO: query agregada no banco ao invés de carregar todas as transações
     */
    public function getAvailableBills(Account $account): Collection
    {
        // Query agregada no banco (1 query ao invés de carregar N transações)
        $billsData = Transaction::where('user_id', $account->user_id)
            ->where('account_id', $account->id)
            ->whereNotNull('bill_identifier')
            ->selectRaw('
                bill_identifier,
                competency_date,
                due_date,
                MIN(transaction_date) as earliest_transaction,
                MAX(transaction_date) as latest_transaction,
                COUNT(*) as transaction_count,
                SUM(ABS(amount)) as total_amount
            ')
            ->groupBy('bill_identifier', 'competency_date', 'due_date')
            ->orderBy('competency_date', 'desc')
            ->get();

        // Buscar categorias separadamente (apenas se necessário)
        $billIdentifiers = $billsData->pluck('bill_identifier')->toArray();

        $categoriesByBill = Transaction::where('user_id', $account->user_id)
            ->where('account_id', $account->id)
            ->whereIn('bill_identifier', $billIdentifiers)
            ->with('category')
            ->selectRaw('bill_identifier, category_id, SUM(ABS(amount)) as total, COUNT(*) as count')
            ->groupBy('bill_identifier', 'category_id')
            ->get()
            ->groupBy('bill_identifier');

        return $billsData->map(function ($bill) use ($categoriesByBill) {
            $categories = ($categoriesByBill->get($bill->bill_identifier) ?? collect())
                ->map(function ($cat) use ($bill) {
                    return [
                        'category_name' => $cat->first()->category->name ?? 'Sem categoria',
                        'total_amount' => (float) $cat->first()->total,
                        'transaction_count' => (int) $cat->first()->count,
                        'percentage' => $bill->total_amount > 0
                            ? round(($cat->first()->total / $bill->total_amount) * 100, 1)
                            : 0,
                    ];
                })
                ->sortByDesc('total_amount')
                ->values()
                ->toArray();

            return [
                'bill_identifier' => $bill->bill_identifier,
                'total_amount' => (float) $bill->total_amount,
                'transaction_count' => (int) $bill->transaction_count,
                'competency_date' => $bill->competency_date,
                'due_date' => $bill->due_date,
                'categories' => $categories,
                'earliest_transaction' => $bill->earliest_transaction,
                'latest_transaction' => $bill->latest_transaction,
            ];
        });
    }

    /**
     * Criar nova fatura para transações futuras
     */
    public function createBillForDate(Carbon $competencyDate, int $dayOfMonth = 10): array
    {
        $dueDate = $competencyDate->copy()->addMonth()->day($dayOfMonth);

        return [
            'competency_date' => $competencyDate->startOfMonth(),
            'due_date' => $dueDate,
            'bill_identifier' => $competencyDate->format('m/Y'),
        ];
    }

    /**
     * Verificar se uma data de transação deve ir para qual competência
     */
    public function determineCompetencyForTransaction(Carbon $transactionDate, int $cutoffDay = 5): array
    {
        // Se a transação é após o dia de corte, vai para a próxima competência
        if ($transactionDate->day > $cutoffDay) {
            $competencyDate = $transactionDate->copy()->addMonth()->startOfMonth();
        } else {
            $competencyDate = $transactionDate->copy()->startOfMonth();
        }

        return $this->createBillForDate($competencyDate);
    }
}
