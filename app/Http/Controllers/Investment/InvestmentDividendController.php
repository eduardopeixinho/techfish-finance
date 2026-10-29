<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentDividendRequest;
use App\Http\Requests\Investment\UpdateInvestmentDividendRequest;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Services\InvestmentReportService;
use Illuminate\Support\Facades\Auth;

class InvestmentDividendController extends Controller
{
    protected InvestmentReportService $reportService;

    public function __construct(InvestmentReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display a listing of the dividends.
     */
    public function index()
    {
        $dividends = Auth::user()->investmentDividends()
            ->with(['investment', 'investment.investmentType'])
            ->orderBy('payment_date', 'desc')
            ->paginate(15);

        // Estatísticas
        $totalReceived = Auth::user()->investmentDividends()->sum('net_amount');
        $currentMonthReceived = Auth::user()->investmentDividends()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('net_amount');
        $lastMonthReceived = Auth::user()->investmentDividends()
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('net_amount');

        return view('investment.dividends.index', compact(
            'dividends',
            'totalReceived',
            'currentMonthReceived',
            'lastMonthReceived'
        ));
    }

    /**
     * Show the form for creating a new dividend.
     */
    public function create()
    {
        $this->authorize('create', InvestmentDividend::class);

        // Buscar investimentos que geralmente pagam proventos
        $investments = Investment::with('investmentType')
            ->where('user_id', auth()->id())
            // Mesmo critério do botão "Registrar provento" na página do ativo
            ->whereHas('investmentType', fn ($query) => $query->where('has_dividends', true))
            ->orderBy('ticker')
            ->get();

        // Pré-seleção vinda da página do ativo (?investment_id=); `investment` mantido por compatibilidade
        $selectedInvestment = null;
        $selectedId = request('investment_id', request('investment'));
        if ($selectedId) {
            $selectedInvestment = Investment::where('user_id', auth()->id())
                ->find($selectedId);
        }

        return view('investment.dividends.create', compact('investments', 'selectedInvestment'));
    }

    /**
     * Store a newly created dividend in storage.
     */
    public function store(StoreInvestmentDividendRequest $request)
    {
        $validated = $request->validated();

        // Verificar se o investimento pertence ao usuário
        $investment = Auth::user()->investments()->findOrFail($validated['investment_id']);

        $amount = $validated['amount_per_share'] * $validated['quantity_owned'];
        $taxWithheld = $validated['tax_withheld'] ?? 0;
        $netAmount = $amount - $taxWithheld;

        InvestmentDividend::create([
            'user_id' => Auth::id(),
            'investment_id' => $validated['investment_id'],
            'type' => $validated['type'],
            'amount' => $amount,
            'amount_per_share' => $validated['amount_per_share'],
            'quantity_owned' => $validated['quantity_owned'],
            'record_date' => $validated['record_date'],
            'payment_date' => $validated['payment_date'],
            'tax_withheld' => $taxWithheld,
            'net_amount' => $netAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('investment.dividends.index')
            ->with('success', 'Provento registrado com sucesso!');
    }

    /**
     * Display the specified dividend.
     */
    public function show(InvestmentDividend $dividend)
    {
        $this->authorize('view', $dividend);
        $dividend->load(['investment', 'investment.investmentType']);

        return view('investment.dividends.show', compact('dividend'));
    }

    /**
     * Show the form for editing the specified dividend.
     */
    public function edit(InvestmentDividend $dividend)
    {
        $this->authorize('update', $dividend);

        // Buscar investimentos que geralmente pagam proventos
        $investments = Investment::with('investmentType')
            ->where('user_id', auth()->id())
            // Mesmo critério do botão "Registrar provento" na página do ativo
            ->whereHas('investmentType', fn ($query) => $query->where('has_dividends', true))
            ->orderBy('ticker')
            ->get();

        return view('investment.dividends.edit', compact('dividend', 'investments'));
    }

    /**
     * Update the specified dividend in storage.
     */
    public function update(UpdateInvestmentDividendRequest $request, InvestmentDividend $dividend)
    {
        $this->authorize('update', $dividend);

        $validated = $request->validated();

        // Verificar se o investimento pertence ao usuário
        $investment = Auth::user()->investments()->findOrFail($validated['investment_id']);

        $amount = $validated['amount_per_share'] * $validated['quantity_owned'];
        $taxWithheld = $validated['tax_withheld'] ?? 0;
        $netAmount = $amount - $taxWithheld;

        $dividend->update([
            'investment_id' => $validated['investment_id'],
            'type' => $validated['type'],
            'amount' => $amount,
            'amount_per_share' => $validated['amount_per_share'],
            'quantity_owned' => $validated['quantity_owned'],
            'record_date' => $validated['record_date'],
            'payment_date' => $validated['payment_date'],
            'tax_withheld' => $taxWithheld,
            'net_amount' => $netAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('investment.dividends.index')
            ->with('success', 'Provento atualizado com sucesso!');
    }

    /**
     * Remove the specified dividend from storage.
     */
    public function destroy(InvestmentDividend $dividend)
    {
        $this->authorize('delete', $dividend);

        $dividend->delete();

        return redirect()->route('investment.dividends.index')
            ->with('success', 'Provento removido com sucesso!');
    }

    /**
     * Display dividend reports.
     */
    public function reports()
    {
        $this->authorize('viewAny', InvestmentDividend::class);

        $filters = [
            'year' => request('year'),
            'month' => request('month'),
            'type' => request('type'),
        ];

        $report = $this->reportService->getDividendReport(auth()->id(), $filters);

        return view('investment.dividends.reports', $report);
    }
}
