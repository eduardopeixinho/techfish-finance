<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentRequest;
use App\Http\Requests\Investment\UpdateInvestmentRequest;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentType;
use App\Services\InvestmentPositionService;
use App\Services\InvestmentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvestmentController extends Controller
{
    /**
     * Display the investment dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Só posições ativas (mesmo critério dos relatórios): ativos vendidos não compõem a carteira
        $totalInvested = $user->investments()->where('is_active', true)->sum('total_invested') ?? 0;
        $currentValue = $user->investments()->where('is_active', true)->sum('current_value') ?? 0;
        $profitLoss = $currentValue - $totalInvested;
        $monthlyDividends = $user->investmentDividends()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('net_amount') ?? 0;

        return view('investment.dashboard', compact(
            'totalInvested',
            'currentValue',
            'profitLoss',
            'monthlyDividends'
        ));
    }

    /**
     * Display a listing of the investments.
     */
    public function index()
    {
        $investments = Auth::user()->investments()
            ->with('investmentType')
            ->orderByDesc('is_active')
            ->orderBy('ticker')
            ->paginate(15);

        // Totais da carteira ATIVA inteira (antes somava só a página atual da paginação). Valores em R$.
        $activeInvestments = Auth::user()->investments()->where('is_active', true);
        $summary = [
            'invested' => (float) (clone $activeInvestments)->sum('total_invested'),
            'current' => (float) (clone $activeInvestments)->sum('current_value'),
            'assets' => (clone $activeInvestments)->count(),
        ];
        $summary['profit_loss'] = $summary['current'] - $summary['invested'];

        return view('investment.investments.index', compact('investments', 'summary'));
    }

    /**
     * Show the form for creating a new investment.
     */
    public function create()
    {
        $investmentTypes = InvestmentType::all();

        return view('investment.investments.create', compact('investmentTypes'));
    }

    /**
     * Store a newly created investment in storage.
     */
    public function store(StoreInvestmentRequest $request, InvestmentPositionService $positions): RedirectResponse
    {
        $validated = $request->validated();

        $investment = Investment::create([
            'user_id' => Auth::id(),
            'investment_type_id' => $validated['investment_type_id'],
            'ticker' => $validated['ticker'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'current_price' => $validated['current_price'] ?? 0,
            'current_quantity' => $validated['current_quantity'] ?? 0,
            'total_invested' => 0,
            'current_value' => 0,
            'profit_loss' => 0,
            'profit_loss_percentage' => 0,
            'is_active' => true,
        ]);
        // Ainda sem lançamentos: valor atual = quantidade digitada × cotação
        $positions->recalculate($investment);

        return redirect()->route('investment.investments.index')
            ->with('success', 'Investimento criado com sucesso!');
    }

    /**
     * Display the specified investment.
     */
    public function show(Investment $investment)
    {
        $this->authorize('view', $investment);

        $investment->load(['investmentType', 'transactions', 'dividends']);

        return view('investment.investments.show', compact('investment'));
    }

    /**
     * Show the form for editing the specified investment.
     */
    public function edit(Investment $investment)
    {
        $this->authorize('update', $investment);

        $investmentTypes = InvestmentType::orderBy('name')->get();

        return view('investment.investments.edit', compact('investment', 'investmentTypes'));
    }

    /**
     * Update the specified investment in storage.
     */
    public function update(UpdateInvestmentRequest $request, Investment $investment, InvestmentPositionService $positions): RedirectResponse
    {
        $validated = $request->validated();

        $investment->update([
            'investment_type_id' => $validated['investment_type_id'],
            'ticker' => $validated['ticker'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'current_price' => $validated['current_price'] ?? $investment->current_price,
            'current_quantity' => $validated['current_quantity'] ?? $investment->current_quantity,
        ]);
        // Com histórico, a quantidade digitada é ignorada: vale a das compras e vendas
        $positions->recalculate($investment);

        return redirect()->route('investment.investments.index')
            ->with('success', 'Investimento atualizado com sucesso!');
    }

    /**
     * Remove the specified investment from storage.
     */
    public function destroy(Investment $investment)
    {
        $this->authorize('delete', $investment);

        $investment->delete();

        return redirect()->route('investment.investments.index')
            ->with('success', 'Investimento removido com sucesso!');
    }

    /**
     * Relatório geral: alocação por tipo e proventos dos últimos 12 meses.
     */
    public function reports(InvestmentReportService $reportService): View
    {
        $stats = $reportService->getInvestmentStats(Auth::id());
        $monthlyDividends = $reportService->getDividendMonthlyEvolution(Auth::id(), 12);

        return view('investment.reports.index', compact('stats', 'monthlyDividends'));
    }

    /**
     * Relatório da carteira ativa, ativo a ativo.
     */
    public function portfolioReport(InvestmentReportService $reportService): View
    {
        $portfolio = $reportService->getPortfolio(Auth::id());

        return view('investment.reports.portfolio', compact('portfolio'));
    }
}
