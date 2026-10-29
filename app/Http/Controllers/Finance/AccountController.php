<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreAccountRequest;
use App\Http\Requests\Finance\UpdateAccountRequest;
use App\Models\Finance\Account;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = Account::forUser(Auth::id())
            ->withCount('transactions')
            ->get();

        return view('finance.accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('finance.accounts.create');
    }

    public function store(StoreAccountRequest $request)
    {
        $this->authorize('create', Account::class);

        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $validated['current_balance'] = $validated['initial_balance'];

        Account::create($validated);

        return redirect()->route('finance.accounts.index')->with('success', 'Conta criada com sucesso!');
    }

    public function show(Account $account)
    {
        $this->authorize('view', $account);

        $transactions = $account->transactions()
            ->with('category')
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return view('finance.accounts.show', compact('account', 'transactions'));
    }

    public function edit(Account $account)
    {
        $this->authorize('update', $account);

        return view('finance.accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        $account->update($request->validated());
        $account->updateBalance();

        return redirect()->route('finance.accounts.index')->with('success', 'Conta atualizada com sucesso!');
    }

    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        if ($account->transactions()->count() > 0) {
            return redirect()->route('finance.accounts.index')
                ->with('error', 'Não é possível excluir uma conta que possui transações.');
        }

        $account->delete();

        return redirect()->route('finance.accounts.index')->with('success', 'Conta excluída com sucesso!');
    }
}
