<?php

namespace App\Services;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FinanceReportService
{
    /**
     * Chave de cache dos relatórios de um usuário/período.
     *
     * Inclui uma "versão" por usuário: `forgetUserReports()` a incrementa e todas as
     * chaves antigas (qualquer período) deixam de ser usadas — sem precisar listá-las.
     */
    public static function reportCacheKey(int $userId, Carbon $startDate, Carbon $endDate): string
    {
        $version = (int) Cache::get(self::versionKey($userId), 1);

        return sprintf('finance.report.%d.v%d.%s.%s', $userId, $version, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
    }

    /**
     * Invalida os relatórios em cache do usuário (chamar após criar/editar/excluir transação).
     */
    public static function forgetUserReports(int $userId): void
    {
        Cache::forever(self::versionKey($userId), (int) Cache::get(self::versionKey($userId), 1) + 1);
    }

    private static function versionKey(int $userId): string
    {
        return 'finance.report.version.'.$userId;
    }

    /**
     * Obtém evolução mensal de receitas e despesas em uma única query.
     * Substitui o loop N+1 que fazia 2 queries por mês.
     *
     * @param  int  $userId  ID do usuário
     * @param  int  $months  Número de meses para trás
     * @return array Array com evolução mensal
     */
    public function getMonthlyEvolution(int $userId, int $months = 6): array
    {
        // startOfMonth antes de subtrair: em dia 31, subMonths() "pularia" meses curtos.
        $currentMonth = Carbon::now()->startOfMonth();
        $startDate = $currentMonth->copy()->subMonths($months - 1);
        $endDate = $currentMonth->copy()->endOfMonth();

        // Uma query só; agrupamento por mês (data da transação = caixa) em PHP para ser
        // portável entre MySQL e SQLite (YEAR()/MONTH() não existem no SQLite dos testes).
        $byMonth = Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereIn('type', ['income', 'expense'])
            ->get(['transaction_date', 'type', 'amount'])
            ->groupBy(fn (Transaction $transaction) => $transaction->transaction_date->format('Y-m'));

        // Monta o array completo incluindo meses sem transações (valores em R$)
        $evolution = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $currentMonth->copy()->subMonths($i);
            $rows = $byMonth->get($date->format('Y-m'), collect());

            $income = (float) $rows->where('type', 'income')->sum('amount');
            $expenses = (float) $rows->where('type', 'expense')->sum('amount');

            $evolution[] = [
                'month' => ucfirst($date->translatedFormat('M/Y')),
                'income' => $income,
                'expense' => $expenses,
                'balance' => $income - $expenses,
            ];
        }

        return $evolution;
    }

    /**
     * Obtém relatório de receitas e despesas por categoria em um período.
     *
     * @param  int  $userId  ID do usuário
     * @param  Carbon  $startDate  Data de início
     * @param  Carbon  $endDate  Data de fim
     * @return array Array com relatórios por categoria
     */
    public function getCategoryReport(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        // Relatório de receitas por categoria
        $incomeReport = Transaction::forUser($userId)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->having('total', '>', 0)
            ->get();

        // Relatório de despesas por categoria
        $expenseReport = Transaction::forUser($userId)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('category')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->having('total', '>', 0)
            ->get();

        return [
            'income' => $incomeReport,
            'expense' => $expenseReport,
        ];
    }

    /**
     * Obtém evolução diária no período.
     *
     * @param  int  $userId  ID do usuário
     * @param  Carbon  $startDate  Data de início
     * @param  Carbon  $endDate  Data de fim
     * @return Collection Coleção agrupada por data
     */
    public function getDailyEvolution(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('transaction_date, type, sum(amount) as total')
            ->groupBy('transaction_date', 'type')
            ->orderBy('transaction_date')
            ->get()
            ->groupBy('transaction_date');
    }

    /**
     * Obtém relatório por contas no período.
     *
     * @param  int  $userId  ID do usuário
     * @param  Carbon  $startDate  Data de início
     * @param  Carbon  $endDate  Data de fim
     * @return Collection Coleção agrupada por conta
     */
    public function getAccountReport(int $userId, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::forUser($userId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with('account')
            ->selectRaw('account_id, type, sum(amount) as total')
            ->groupBy('account_id', 'type')
            ->get()
            ->groupBy('account_id');
    }

    /**
     * Obtém distribuição de gastos por categoria no mês especificado.
     *
     * @param  int  $userId  ID do usuário
     * @param  int|null  $month  Mês (1-12), null = mês atual
     * @param  int|null  $year  Ano, null = ano atual
     * @return Collection Coleção com gastos por categoria
     */
    public function getExpensesByCategory(int $userId, ?int $month = null, ?int $year = null): Collection
    {
        $query = Transaction::forUser($userId)
            ->where('type', 'expense')
            ->with('category');

        if ($month && $year) {
            $query->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year);
        } else {
            $query->thisMonth();
        }

        return $query->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->having('total', '>', 0)
            ->get();
    }
}
