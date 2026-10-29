<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Settings\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index()
    {
        $query = Transaction::forUser(Auth::id())
            ->with(['account', 'category', 'transferAccount']);

        // Filtros
        if (request('type')) {
            $query->byType(request('type'));
        }

        if (request('account_id')) {
            $query->byAccount(request('account_id'));
        }

        if (request('category_id')) {
            $query->byCategory(request('category_id'));
        }

        if (request('start_date') && request('end_date')) {
            $query->byDateRange(request('start_date'), request('end_date'));
        } elseif (request('start_date')) {
            $query->where('transaction_date', '>=', request('start_date'));
        } elseif (request('end_date')) {
            $query->where('transaction_date', '<=', request('end_date'));
        }

        // Busca textual
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Dados para os filtros
        $accounts = Account::forUser(Auth::id())->active()->orderBy('name')->get();
        $categories = FinancialCategory::forUser(Auth::id())->active()->orderBy('name')->get();

        return view('finance.transactions.index', compact('transactions', 'accounts', 'categories'));
    }

    public function create()
    {
        $accounts = Account::forUser(Auth::id())->active()->get();
        $incomeCategories = FinancialCategory::forUser(Auth::id())->byType('income')->active()->get();
        $expenseCategories = FinancialCategory::forUser(Auth::id())->byType('expense')->active()->get();
        $paymentMethods = PaymentMethod::active()->orderBy('name')->get();

        return view('finance.transactions.create', compact('accounts', 'incomeCategories', 'expenseCategories', 'paymentMethods'));
    }

    public function store(StoreTransactionRequest $request)
    {
        $this->transactionService->create($request->validated());

        return redirect()->route('finance.transactions.index')->with('success', 'Transação criada com sucesso!');
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        return view('finance.transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $accounts = Account::forUser(Auth::id())->active()->get();
        $incomeCategories = FinancialCategory::forUser(Auth::id())->byType('income')->active()->get();
        $expenseCategories = FinancialCategory::forUser(Auth::id())->byType('expense')->active()->get();
        $paymentMethods = PaymentMethod::active()->orderBy('name')->get();

        return view('finance.transactions.edit', compact('transaction', 'accounts', 'incomeCategories', 'expenseCategories', 'paymentMethods'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $this->transactionService->update($transaction, $request->validated());

        return redirect()->route('finance.transactions.index')->with('success', 'Transação atualizada com sucesso!');
    }

    public function destroy(Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        $this->transactionService->delete($transaction);

        return redirect()->route('finance.transactions.index')->with('success', 'Transação excluída com sucesso!');
    }
}
