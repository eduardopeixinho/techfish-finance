<?php

namespace App\Services;

use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Faturas de cartão de crédito.
 *
 * Convenções (valores em R$):
 *  - A fatura é identificada pelo mês do VENCIMENTO: "MM-AAAA" (ex.: "09-2025" vence em setembro).
 *  - Compra antes do dia de fechamento entra na fatura que fecha neste mês; no dia do fechamento
 *    ou depois, na que fecha no mês seguinte. O vencimento é o próximo `due_day` após o fechamento.
 *  - Total = despesas − créditos (estornos) do cartão naquela fatura. Transferências não entram.
 *  - Pago = soma dos BillPayment da fatura; status calculado em billStatus().
 */
class CreditCardBillService
{
    /** Fatura vence em até N dias: "Vence em breve". */
    private const DUE_SOON_DAYS = 7;

    /**
     * Fatura em que cai uma compra/estorno feita em $date no cartão.
     *
     * @return array{bill_identifier: string, competency_date: string, due_date: string}
     */
    public function billFor(Account $card, CarbonInterface|string $date): array
    {
        $closingDay = $card->closing_day ?: Account::DEFAULT_CLOSING_DAY;
        $dueDay = $card->due_day ?: Account::DEFAULT_DUE_DAY;

        $date = Carbon::parse($date)->startOfDay();
        $closingThisMonth = $this->dayOfMonth($date->copy()->startOfMonth(), $closingDay);
        $closing = $date->lt($closingThisMonth)
            ? $closingThisMonth
            : $this->dayOfMonth($date->copy()->startOfMonth()->addMonthNoOverflow(), $closingDay);

        // Vencimento: o próximo dia `due_day` depois do fechamento
        $dueMonth = $dueDay > $closingDay
            ? $closing->copy()->startOfMonth()
            : $closing->copy()->startOfMonth()->addMonthNoOverflow();

        return $this->billInMonth($card, $dueMonth);
    }

    /**
     * Fatura escolhida pelo identificador ("MM-AAAA"), ex.: o usuário mudou a fatura no formulário.
     *
     * @return array{bill_identifier: string, competency_date: string, due_date: string}
     */
    public function billFromIdentifier(Account $card, string $billIdentifier): array
    {
        return $this->billInMonth($card, Carbon::createFromFormat('!m-Y', $billIdentifier));
    }

    /**
     * Faturas oferecidas no formulário de transação: 3 meses antes e 3 depois do mês atual
     * (mais a fatura atual do lançamento, se estiver fora dessa janela).
     *
     * @return array<string, string> identificador => rótulo ("Novembro de 2026")
     */
    public function selectableBills(?string $include = null): array
    {
        $options = [];
        for ($offset = -3; $offset <= 3; $offset++) {
            $month = Carbon::today()->startOfMonth()->addMonthsNoOverflow($offset);
            $options[$month->format('m-Y')] = ucfirst($month->translatedFormat('F \d\e Y'));
        }

        if ($include && ! isset($options[$include]) && preg_match('/^\d{2}-\d{4}$/', $include)) {
            $month = Carbon::createFromFormat('!m-Y', $include);
            $options = [$include => ucfirst($month->translatedFormat('F \d\e Y'))] + $options;
        }

        return $options;
    }

    /**
     * Lançamentos de uma fatura (com categoria).
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
     * Resumo de uma fatura: total, pago, em aberto, status, pagamentos e categorias.
     *
     * @return array<string, mixed>
     */
    public function getBillSummary(Account $account, string $billIdentifier): array
    {
        $transactions = $this->getTransactionsByBill($account, $billIdentifier);
        $payments = $this->payments($account, $billIdentifier);
        $paid = round((float) $payments->sum('amount'), 2);

        $total = round($transactions->sum(fn (Transaction $t): float => $this->signedAmount($t->type, (float) $t->amount)), 2);
        $dueDate = $transactions->max('due_date');

        return [
            'bill_identifier' => $billIdentifier,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0.0, round($total - $paid, 2)),
            'status' => $this->billStatus($total, $paid, $dueDate),
            'payments' => $payments,
            'transaction_count' => $transactions->count(),
            'competency_date' => $transactions->min('competency_date'),
            'due_date' => $dueDate,
            'categories' => $this->getCategoryBreakdown($transactions),
            'earliest_transaction' => $transactions->min('transaction_date'),
            'latest_transaction' => $transactions->max('transaction_date'),
        ];
    }

    /**
     * Todas as faturas de um cartão, da mais recente para a mais antiga (agregado no banco).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAvailableBills(Account $account): Collection
    {
        $billsData = Transaction::where('user_id', $account->user_id)
            ->where('account_id', $account->id)
            ->whereNotNull('bill_identifier')
            ->selectRaw("
                bill_identifier,
                MIN(competency_date) as competency_date,
                MAX(due_date) as due_date,
                MIN(transaction_date) as earliest_transaction,
                MAX(transaction_date) as latest_transaction,
                COUNT(*) as transaction_count,
                SUM(CASE WHEN type = 'expense' THEN ABS(amount) WHEN type = 'income' THEN -ABS(amount) ELSE 0 END) as total_amount
            ")
            ->groupBy('bill_identifier')
            ->orderByDesc('due_date')
            ->get();

        $paidByBill = BillPayment::where('account_id', $account->id)
            ->selectRaw('bill_identifier, SUM(amount) as paid')
            ->groupBy('bill_identifier')
            ->pluck('paid', 'bill_identifier');

        return $billsData->map(function ($bill) use ($paidByBill): array {
            $total = round((float) $bill->total_amount, 2);
            $paid = round((float) ($paidByBill[$bill->bill_identifier] ?? 0), 2);

            return [
                'bill_identifier' => $bill->bill_identifier,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => max(0.0, round($total - $paid, 2)),
                'status' => $this->billStatus($total, $paid, $bill->due_date),
                'transaction_count' => (int) $bill->transaction_count,
                'competency_date' => $bill->competency_date,
                'due_date' => $bill->due_date,
                'earliest_transaction' => $bill->earliest_transaction,
                'latest_transaction' => $bill->latest_transaction,
            ];
        });
    }

    /**
     * Status da fatura: [rótulo, tom do <x-ui.badge>].
     *
     * @return array{0: string, 1: string}
     */
    public function billStatus(float $total, float $paid, CarbonInterface|string|null $dueDate): array
    {
        if ($total <= 0) {
            return ['Sem valor a pagar', 'neutral'];
        }
        if ($paid >= $total - 0.009) {
            return ['Paga', 'income'];
        }
        if ($paid > 0) {
            return ['Paga em parte', 'warning'];
        }
        if (! $dueDate) {
            return ['Sem vencimento', 'neutral'];
        }

        $today = Carbon::today();
        $due = Carbon::parse($dueDate)->startOfDay();

        return match (true) {
            $due->lt($today) => ['Vencida', 'expense'],
            $today->diffInDays($due) <= self::DUE_SOON_DAYS => ['Vence em breve', 'warning'],
            default => ['Em aberto', 'neutral'],
        };
    }

    /**
     * Pagamentos de uma fatura, do mais antigo para o mais recente.
     *
     * @return Collection<int, BillPayment>
     */
    public function payments(Account $account, string $billIdentifier): Collection
    {
        return BillPayment::with('transaction.account')
            ->where('account_id', $account->id)
            ->where('bill_identifier', $billIdentifier)
            ->orderBy('paid_at')
            ->get();
    }

    /**
     * Valor do lançamento no total da fatura: despesa soma, crédito (estorno) subtrai.
     * Usa o valor absoluto porque dados importados podem ter o sinal no próprio valor.
     */
    private function signedAmount(string $type, float $amount): float
    {
        return match ($type) {
            'expense' => abs($amount),
            'income' => -abs($amount),
            default => 0.0,
        };
    }

    /**
     * Total por categoria (despesas − créditos), do maior para o menor.
     *
     * @return array<int, array{category_name: string, total_amount: float, transaction_count: int, percentage: float}>
     */
    private function getCategoryBreakdown(Collection $transactions): array
    {
        return $transactions
            ->groupBy(fn (Transaction $transaction): string => $transaction->category->name ?? 'Sem categoria')
            ->map(fn (Collection $items, string $categoryName): array => [
                'category_name' => $categoryName,
                'total_amount' => round($items->sum(fn (Transaction $t): float => $this->signedAmount($t->type, (float) $t->amount)), 2),
                'transaction_count' => $items->count(),
                'percentage' => 0,
            ])
            ->sortByDesc('total_amount')
            ->values()
            ->toArray();
    }

    /**
     * @return array{bill_identifier: string, competency_date: string, due_date: string}
     */
    private function billInMonth(Account $card, CarbonInterface $dueMonth): array
    {
        $dueDay = $card->due_day ?: Account::DEFAULT_DUE_DAY;
        $month = Carbon::parse($dueMonth)->startOfMonth();

        return [
            'bill_identifier' => $month->format('m-Y'),
            'competency_date' => $month->toDateString(),
            'due_date' => $this->dayOfMonth($month, $dueDay)->toDateString(),
        ];
    }

    /**
     * Dia do mês sem estourar: dia 31 em fevereiro vira o último dia de fevereiro.
     */
    private function dayOfMonth(CarbonInterface $month, int $day): Carbon
    {
        $month = Carbon::parse($month)->startOfMonth();

        return $month->copy()->day(min($day, $month->daysInMonth));
    }
}
