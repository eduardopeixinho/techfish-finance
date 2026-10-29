<?php

namespace App\Http\Controllers\Finance\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Settings\StoreAccountTypeRequest;
use App\Http\Requests\Finance\Settings\UpdateAccountTypeRequest;
use App\Models\Finance\Settings\AccountType;

class AccountTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.super');
    }

    public function index()
    {
        $accountTypes = AccountType::orderBy('name')->paginate(15);

        return view('finance.settings.account-types.index', compact('accountTypes'));
    }

    public function create()
    {
        return view('finance.settings.account-types.create');
    }

    public function store(StoreAccountTypeRequest $request)
    {
        AccountType::create($request->validated());

        return redirect()->route('settings.account-types.index')
            ->with('success', 'Tipo de conta criado com sucesso!');
    }

    public function show(AccountType $accountType)
    {
        return view('finance.settings.account-types.show', compact('accountType'));
    }

    public function edit(AccountType $accountType)
    {
        return view('finance.settings.account-types.edit', compact('accountType'));
    }

    public function update(UpdateAccountTypeRequest $request, AccountType $accountType)
    {
        $accountType->update($request->validated());

        return redirect()->route('settings.account-types.index')
            ->with('success', 'Tipo de conta atualizado com sucesso!');
    }

    public function destroy(AccountType $accountType)
    {
        $accountType->delete();

        return redirect()->route('settings.account-types.index')
            ->with('success', 'Tipo de conta excluído com sucesso!');
    }
}
