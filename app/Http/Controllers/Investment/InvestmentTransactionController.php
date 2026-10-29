<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentTransactionRequest;
use App\Http\Requests\Investment\UpdateInvestmentTransactionRequest;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use App\Services\InvestmentPositionService;
use Illuminate\Support\Facades\DB;

/**
 * Compras e vendas de ativos. Cada mudança recalcula a posição do ativo
 * (quantidade, custo médio e valor) pelo InvestmentPositionService.
 */
class InvestmentTransactionController extends Controller
{
    public function __construct(private InvestmentPositionService $positions) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = InvestmentTransaction::with(['investment', 'investment.investmentType'])
            ->where('user_id', auth()->id())
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        // Estatísticas
        $stats = [
            'total_transactions' => InvestmentTransaction::where('user_id', auth()->id())->count(),
            'total_invested' => InvestmentTransaction::where('user_id', auth()->id())
                ->where('type', 'buy')
                ->sum('total_amount'),
            'total_received' => InvestmentTransaction::where('user_id', auth()->id())
                ->where('type', 'sell')
                ->sum('total_amount'),
            'average_transaction' => InvestmentTransaction::where('user_id', auth()->id())->avg('total_amount'),
        ];

        return view('investment.transactions.index', compact('transactions', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $investments = Investment::where('user_id', auth()->id())
            ->with('investmentType')
            ->orderBy('ticker')
            ->get();

        // Pré-seleção vinda da página do ativo (?investment_id=); `investment` mantido por compatibilidade
        $selectedInvestment = null;
        $selectedId = request('investment_id', request('investment'));
        if ($selectedId) {
            $selectedInvestment = Investment::where('user_id', auth()->id())
                ->find($selectedId);
        }

        return view('investment.transactions.create', compact('investments', 'selectedInvestment'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvestmentTransactionRequest $request)
    {
        $validated = $request->validated();

        // Verificar se o investimento pertence ao usuário
        $investment = Investment::where('user_id', auth()->id())
            ->findOrFail($validated['investment_id']);

        // Calcular valores
        $totalAmount = $validated['quantity'] * $validated['price'];
        $fees = $validated['fees'] ?? 0;
        $netAmount = $totalAmount + $fees;

        $transaction = DB::transaction(fn () => tap(InvestmentTransaction::create([
            'user_id' => auth()->id(),
            'investment_id' => $validated['investment_id'],
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'total_amount' => $totalAmount,
            'fees' => $fees,
            'net_amount' => $netAmount,
            'transaction_date' => $validated['transaction_date'],
            'notes' => $validated['notes'] ?? null,
        ]), fn () => $this->positions->recalculate($investment)));

        return redirect()->route('investment.transactions.index')
            ->with('success', 'Transação registrada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(InvestmentTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        return view('investment.transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InvestmentTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $investments = Investment::where('user_id', auth()->id())
            ->with('investmentType')
            ->orderBy('ticker')
            ->get();

        return view('investment.transactions.edit', compact('transaction', 'investments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvestmentTransactionRequest $request, InvestmentTransaction $transaction)
    {
        $this->authorize('update', $transaction);

        $validated = $request->validated();

        // Verificar se o investimento pertence ao usuário
        $investment = Investment::where('user_id', auth()->id())
            ->findOrFail($validated['investment_id']);

        // Calcular valores (mesma lógica do store)
        $totalAmount = $validated['quantity'] * $validated['price'];
        $fees = $validated['fees'] ?? 0;
        $netAmount = $totalAmount + $fees;

        $previousInvestment = $transaction->investment;

        DB::transaction(function () use ($transaction, $validated, $totalAmount, $fees, $netAmount, $investment, $previousInvestment): void {
            $transaction->update([
                'investment_id' => $validated['investment_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'price' => $validated['price'],
                'total_amount' => $totalAmount,
                'fees' => $fees,
                'net_amount' => $netAmount,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->positions->recalculate($investment);
            if ($previousInvestment && ! $previousInvestment->is($investment)) {
                $this->positions->recalculate($previousInvestment, fromHistory: true);
            }
        });

        return redirect()->route('investment.transactions.show', $transaction)
            ->with('success', 'Transação atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvestmentTransaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $investment = $transaction->investment;

        // Excluir uma compra não pode deixar uma venda posterior maior que a posição
        if ($investment && ! $this->positions->historyStaysValid($investment, $transaction)) {
            return redirect()->back()
                ->with('error', 'Não dá para excluir esta compra: uma venda posterior ficaria maior que a posição. Exclua ou ajuste a venda antes.');
        }

        DB::transaction(function () use ($transaction, $investment): void {
            $transaction->delete();
            if ($investment) {
                $this->positions->recalculate($investment);
            }
        });

        return redirect()->route('investment.transactions.index')
            ->with('success', 'Transação excluída com sucesso!');
    }
}
