<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Models\Finance\FinancialCategory;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $incomeCategories = FinancialCategory::forUser(Auth::id())
            ->byType('income')
            ->active()
            ->withCount('transactions')
            ->get();

        $expenseCategories = FinancialCategory::forUser(Auth::id())
            ->byType('expense')
            ->active()
            ->withCount('transactions')
            ->get();

        return view('finance.categories.index', compact('incomeCategories', 'expenseCategories'));
    }

    public function create()
    {
        return view('finance.categories.create');
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();

        FinancialCategory::create($validated);

        return redirect()->route('finance.categories.index')->with('success', 'Categoria criada com sucesso!');
    }

    public function show(FinancialCategory $category)
    {
        $this->authorize('view', $category);

        $transactions = $category->transactions()
            ->with('account')
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return view('finance.categories.show', compact('category', 'transactions'));
    }

    public function edit(FinancialCategory $category)
    {
        $this->authorize('update', $category);

        return view('finance.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, FinancialCategory $category)
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return redirect()->route('finance.categories.index')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(FinancialCategory $category)
    {
        $this->authorize('delete', $category);

        if ($category->transactions()->count() > 0) {
            return redirect()->route('finance.categories.index')
                ->with('error', 'Não é possível excluir uma categoria que possui transações.');
        }

        $category->delete();

        return redirect()->route('finance.categories.index')->with('success', 'Categoria excluída com sucesso!');
    }
}
