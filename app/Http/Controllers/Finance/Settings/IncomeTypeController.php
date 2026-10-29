<?php

namespace App\Http\Controllers\Finance\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Settings\StoreIncomeTypeRequest;
use App\Http\Requests\Finance\Settings\UpdateIncomeTypeRequest;
use App\Models\Finance\Settings\IncomeType;

class IncomeTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.super');
    }

    public function index()
    {
        $incomeTypes = IncomeType::orderBy('name')->paginate(15);

        return view('finance.settings.income-types.index', compact('incomeTypes'));
    }

    public function create()
    {
        return view('finance.settings.income-types.create');
    }

    public function store(StoreIncomeTypeRequest $request)
    {
        IncomeType::create($request->validated());

        return redirect()->route('settings.income-types.index')
            ->with('success', 'Tipo de receita criado com sucesso!');
    }

    public function show(IncomeType $incomeType)
    {
        return view('finance.settings.income-types.show', compact('incomeType'));
    }

    public function edit(IncomeType $incomeType)
    {
        return view('finance.settings.income-types.edit', compact('incomeType'));
    }

    public function update(UpdateIncomeTypeRequest $request, IncomeType $incomeType)
    {
        $incomeType->update($request->validated());

        return redirect()->route('settings.income-types.index')
            ->with('success', 'Tipo de receita atualizado com sucesso!');
    }

    public function destroy(IncomeType $incomeType)
    {
        $incomeType->delete();

        return redirect()->route('settings.income-types.index')
            ->with('success', 'Tipo de receita excluído com sucesso!');
    }
}
