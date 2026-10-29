<?php

namespace App\Http\Controllers\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentTypeRequest;
use App\Http\Requests\Investment\UpdateInvestmentTypeRequest;
use App\Models\Investment\InvestmentType;

class InvestmentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.super');
    }

    /**
     * Display a listing of the investment types.
     */
    public function index()
    {
        $investmentTypes = InvestmentType::withCount('investments')->orderBy('name')->paginate(15);

        return view('investment.types.index', compact('investmentTypes'));
    }

    /**
     * Show the form for creating a new investment type.
     */
    public function create()
    {
        return view('investment.types.create');
    }

    /**
     * Store a newly created investment type in storage.
     */
    public function store(StoreInvestmentTypeRequest $request)
    {
        $data = $request->validated();
        $data['code'] = strtolower($data['code']);
        $data['has_dividends'] = $data['has_dividends'] ?? false;

        InvestmentType::create($data);

        return redirect()->route('investment.types.index')
            ->with('success', 'Tipo de investimento criado com sucesso!');
    }

    /**
     * Display the specified investment type.
     */
    public function show(InvestmentType $type)
    {
        // Isolamento: o catálogo é global, mas os ativos são de cada usuário. O admin vê só
        // o total agregado (para saber se o tipo pode ser excluído) e os PRÓPRIOS ativos.
        $type->loadCount('investments');
        $myInvestments = $type->investments()
            ->where('user_id', auth()->id())
            ->orderBy('ticker')
            ->get();

        return view('investment.types.show', compact('type', 'myInvestments'));
    }

    /**
     * Show the form for editing the specified investment type.
     */
    public function edit(InvestmentType $type)
    {
        return view('investment.types.edit', compact('type'));
    }

    /**
     * Update the specified investment type in storage.
     */
    public function update(UpdateInvestmentTypeRequest $request, InvestmentType $type)
    {
        $data = $request->validated();
        $data['code'] = strtolower($data['code']);
        $data['has_dividends'] = $data['has_dividends'] ?? false;

        $type->update($data);

        return redirect()->route('investment.types.index')
            ->with('success', 'Tipo de investimento atualizado com sucesso!');
    }

    /**
     * Remove the specified investment type from storage.
     */
    public function destroy(InvestmentType $type)
    {
        if ($type->investments()->count() > 0) {
            return redirect()->route('investment.types.index')
                ->with('error', 'Não é possível excluir este tipo pois existem investimentos vinculados a ele.');
        }

        $type->delete();

        return redirect()->route('investment.types.index')
            ->with('success', 'Tipo de investimento removido com sucesso!');
    }
}
