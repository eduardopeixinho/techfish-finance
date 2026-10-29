<?php

namespace App\Services;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class InvestmentReportService
{
    /**
     * Obtém relatório de dividendos com filtros opcionais.
     *
     * @param  int  $userId  ID do usuário
     * @param  array  $filters  Filtros opcionais (year, month, type)
     * @return array Array com dados do relatório
     */
    public function getDividendReport(int $userId, array $filters = []): array
    {
        $query = InvestmentDividend::with(['investment.investmentType'])
            ->where('user_id', $userId);

        // Aplicar filtros
        if (! empty($filters['year'])) {
            $query->whereYear('payment_date', $filters['year']);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth('payment_date', $filters['month']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $dividends = $query->orderBy('payment_date', 'desc')->get();

        // Calcular totais
        $totalGross = $dividends->sum('amount');
        $totalTax = $dividends->sum('tax_withheld');
        $totalNet = $dividends->sum('net_amount');

        // Resumo por tipo
        $byType = $dividends->groupBy('type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'gross' => $group->sum('amount'),
                'tax' => $group->sum('tax_withheld'),
                'net' => $group->sum('net_amount'),
            ];
        });

        // Resumo por investimento
        $byInvestment = $dividends->groupBy('investment_id')->map(function ($group) {
            $investment = $group->first()->investment;

            return [
                'ticker' => $investment->ticker,
                'name' => $investment->name,
                'type' => $investment->investmentType->name ?? 'N/A',
                'count' => $group->count(),
                'gross' => $group->sum('amount'),
                'net' => $group->sum('net_amount'),
                'avg_yield' => $investment->current_price > 0 ?
                    ($group->sum('amount') / ($investment->current_price * $group->sum('quantity_owned'))) * 100 : 0,
            ];
        });

        // Evolução mensal
        $monthlyEvolution = $dividends->groupBy(function ($dividend) {
            return $dividend->payment_date->format('Y-m');
        })->map(function ($group, $monthYear) {
            return (object) [
                'month_year' => \DateTime::createFromFormat('Y-m', $monthYear)->format('m/Y'),
                'count' => $group->count(),
                'gross' => $group->sum('amount'),
                'tax' => $group->sum('tax_withheld'),
                'net' => $group->sum('net_amount'),
            ];
        })->sortKeys();

        return [
            'dividends' => $dividends,
            'totalGross' => $totalGross,
            'totalTax' => $totalTax,
            'totalNet' => $totalNet,
            'byType' => $byType,
            'byInvestment' => $byInvestment,
            'monthlyEvolution' => $monthlyEvolution,
        ];
    }

    /**
     * Obtém estatísticas de investimentos do usuário.
     *
     * @param  int  $userId  ID do usuário
     * @return array Array com estatísticas
     */
    public function getInvestmentStats(int $userId): array
    {
        // Só posições ativas: ativos vendidos (is_active = false) não compõem a carteira atual.
        $investments = Investment::where('user_id', $userId)
            ->where('is_active', true)
            ->with('investmentType')
            ->get();

        $totalInvested = $investments->sum('total_invested');
        $currentValue = $investments->sum('current_value');
        $profitLoss = $currentValue - $totalInvested;
        $profitLossPercentage = $totalInvested > 0 ? ($profitLoss / $totalInvested) * 100 : 0;

        // Por tipo de investimento
        $byType = $investments->groupBy(function ($investment) {
            return $investment->investmentType->name ?? 'Outros';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_invested' => $group->sum('total_invested'),
                'current_value' => $group->sum('current_value'),
                'profit_loss' => $group->sum('current_value') - $group->sum('total_invested'),
            ];
        });

        return [
            'total_invested' => $totalInvested,
            'current_value' => $currentValue,
            'profit_loss' => $profitLoss,
            'profit_loss_percentage' => $profitLossPercentage,
            'total_investments' => $investments->count(),
            'by_type' => $byType,
        ];
    }

    /**
     * Carteira ativa do usuário, ativo a ativo, com proventos recebidos.
     *
     * Valores em R$. `yield_on_cost` = proventos líquidos recebidos / total investido (%).
     *
     * @return array{items: Collection<int, array<string, mixed>>, totals: array<string, float>}
     */
    public function getPortfolio(int $userId): array
    {
        $investments = Investment::where('user_id', $userId)
            ->where('is_active', true)
            ->with('investmentType')
            ->withSum('dividends as dividends_received', 'net_amount')
            ->orderBy('ticker')
            ->get();

        $items = $investments->map(function (Investment $investment): array {
            $invested = (float) $investment->total_invested;
            $current = (float) $investment->current_value;
            $dividends = (float) ($investment->dividends_received ?? 0);

            return [
                'ticker' => $investment->ticker,
                'name' => $investment->name,
                'type' => $investment->investmentType->name ?? 'Outros',
                'quantity' => (float) $investment->current_quantity,
                'invested' => $invested,
                'current' => $current,
                'profit_loss' => $current - $invested,
                'profit_loss_percentage' => $invested > 0 ? (($current - $invested) / $invested) * 100 : 0.0,
                'dividends' => $dividends,
                'yield_on_cost' => $invested > 0 ? ($dividends / $invested) * 100 : 0.0,
                'url' => route('investment.investments.show', $investment),
            ];
        });

        $totalInvested = (float) $items->sum('invested');
        $totalCurrent = (float) $items->sum('current');

        return [
            'items' => $items,
            'totals' => [
                'invested' => $totalInvested,
                'current' => $totalCurrent,
                'profit_loss' => $totalCurrent - $totalInvested,
                'profit_loss_percentage' => $totalInvested > 0 ? (($totalCurrent - $totalInvested) / $totalInvested) * 100 : 0.0,
                'dividends' => (float) $items->sum('dividends'),
            ],
        ];
    }

    /**
     * Obtém evolução mensal de dividendos.
     *
     * @param  int  $userId  ID do usuário
     * @param  int  $months  Número de meses para trás
     * @return array Array com evolução mensal
     */
    public function getDividendMonthlyEvolution(int $userId, int $months = 12): array
    {
        // startOfMonth antes de subtrair: em dia 31, subMonths() "pularia" meses curtos.
        $currentMonth = Carbon::now()->startOfMonth();
        $startDate = $currentMonth->copy()->subMonths($months - 1);
        $endDate = $currentMonth->copy()->endOfMonth();

        // Agrupa por mês (competência = data de pagamento) em PHP: portável entre
        // MySQL e SQLite (YEAR()/MONTH() não existem no SQLite dos testes) e o volume é pequeno.
        $byMonth = InvestmentDividend::where('user_id', $userId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get(['payment_date', 'amount', 'tax_withheld', 'net_amount'])
            ->groupBy(fn (InvestmentDividend $dividend) => $dividend->payment_date->format('Y-m'));

        // Monta o array completo incluindo meses sem dividendos (valores em R$)
        $evolution = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $currentMonth->copy()->subMonths($i);
            $rows = $byMonth->get($date->format('Y-m'), collect());

            $evolution[] = [
                'month' => $date->translatedFormat('M/Y'),
                'gross' => (float) $rows->sum('amount'),
                'tax' => (float) $rows->sum('tax_withheld'),
                'net' => (float) $rows->sum('net_amount'),
            ];
        }

        return $evolution;
    }

    /**
     * Obtém dividendos do mês atual e anterior para comparação.
     *
     * @param  int  $userId  ID do usuário
     * @return array Array com totais dos meses
     */
    public function getDividendComparison(int $userId): array
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentMonthReceived = InvestmentDividend::where('user_id', $userId)
            ->whereMonth('payment_date', $currentMonth->month)
            ->whereYear('payment_date', $currentMonth->year)
            ->sum('net_amount');

        $lastMonthReceived = InvestmentDividend::where('user_id', $userId)
            ->whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->sum('net_amount');

        $totalReceived = InvestmentDividend::where('user_id', $userId)->sum('net_amount');

        return [
            'total_received' => $totalReceived,
            'current_month' => $currentMonthReceived,
            'last_month' => $lastMonthReceived,
            'variation' => $lastMonthReceived > 0
                ? (($currentMonthReceived - $lastMonthReceived) / $lastMonthReceived) * 100
                : 0,
        ];
    }
}
