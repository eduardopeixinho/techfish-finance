<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ReportRequest;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Services\FinanceReportService;
use App\Services\OnboardingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FinanceController extends Controller
{
    protected FinanceReportService $reportService;

    public function __construct(FinanceReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Mês/ano selecionado (padrão: mês atual)
        $selectedMonth = $request->get('month', Carbon::now()->month);
        $selectedYear = $request->get('year', Carbon::now()->year);
        $currentMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1);

        // Resumo geral (OTIMIZADO: 1 query ao invés de 3)
        $summary = $user->getFinancialSummary($selectedMonth, $selectedYear);
        $totalBalance = $summary['totalBalance'];
        $monthlyIncome = $summary['monthlyIncome'];
        $monthlyExpenses = $summary['monthlyExpenses'];
        $monthlyBalance = $monthlyIncome - $monthlyExpenses;

        // Transações recentes
        $recentTransactions = Transaction::forUser($user->id)
            ->with(['account', 'category', 'transferAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Contas ativas
        $accounts = Account::forUser($user->id)
            ->active()
            ->get();

        // Distribuição de gastos por categoria (mês selecionado)
        $expensesByCategory = $this->reportService->getExpensesByCategory($user->id, $selectedMonth, $selectedYear);

        // Evolução mensal (últimos 6 meses) - OTIMIZADO: 1 query ao invés de 12
        $monthlyEvolution = $this->reportService->getMonthlyEvolution($user->id, 6);

        // Primeiros passos (null quando conta, categorias e primeira transação já existem)
        $onboarding = app(OnboardingService::class)->steps($user);

        return view('finance.dashboard', compact(
            'onboarding',
            'totalBalance',
            'monthlyIncome',
            'monthlyExpenses',
            'monthlyBalance',
            'recentTransactions',
            'accounts',
            'expensesByCategory',
            'monthlyEvolution',
            'selectedMonth',
            'selectedYear'
        ));
    }

    public function reports(ReportRequest $request)
    {
        $user = Auth::user();

        // Definir datas padrão se não fornecidas
        $startDate = $request->get('start_date') ?
            Carbon::parse($request->get('start_date')) :
            Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ?
            Carbon::parse($request->get('end_date')) :
            Carbon::now()->endOfMonth();

        // Chave por usuário + período + versão (invalidada ao lançar/editar/excluir transação)
        $cacheKey = FinanceReportService::reportCacheKey($user->id, $startDate, $endDate);

        // Obter relatórios do service (com cache de 5 minutos)
        $reportData = Cache::remember($cacheKey, 300, function () use ($user, $startDate, $endDate) {
            $categoryReport = $this->reportService->getCategoryReport($user->id, $startDate, $endDate);
            $incomeReport = $categoryReport['income'];
            $expenseReport = $categoryReport['expense'];
            $dailyEvolution = $this->reportService->getDailyEvolution($user->id, $startDate, $endDate);
            $accountReport = $this->reportService->getAccountReport($user->id, $startDate, $endDate);
            $monthlyComparison = $this->reportService->getMonthlyEvolution($user->id, 6);

            return [
                'incomeReport' => $incomeReport,
                'expenseReport' => $expenseReport,
                'dailyEvolution' => $dailyEvolution,
                'accountReport' => $accountReport,
                'monthlyComparison' => $monthlyComparison,
            ];
        });

        // Formatação das datas para exibição
        $startDateFormatted = $startDate->format('Y-m-d');
        $endDateFormatted = $endDate->format('Y-m-d');

        return view('finance.reports', array_merge($reportData, [
            'startDateFormatted' => $startDateFormatted,
            'endDateFormatted' => $endDateFormatted,
        ]));
    }

    public function exportReportCSV(ReportRequest $request)
    {
        $user = Auth::user();

        $startDate = $request->get('start_date') ?
            Carbon::parse($request->get('start_date')) :
            Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ?
            Carbon::parse($request->get('end_date')) :
            Carbon::now()->endOfMonth();

        // OTIMIZADO: usar cursor() ao invés de get() para economizar memória
        $transactions = Transaction::forUser($user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['account', 'category'])
            ->orderBy('transaction_date', 'desc')
            ->cursor();

        $filename = 'relatorio_financeiro_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Header do CSV
            fputcsv($file, [
                'Data',
                'Descrição',
                'Categoria',
                'Conta',
                'Tipo',
                'Valor',
            ]);

            // Dados (streaming com cursor)
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_date,
                    $transaction->description,
                    $transaction->category->name ?? 'Sem categoria',
                    $transaction->account->name,
                    $transaction->type === 'income' ? 'Receita' : 'Despesa',
                    number_format($transaction->amount, 2, ',', '.'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
