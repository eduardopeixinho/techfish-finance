<?php

namespace App\Http\Controllers\Finance\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Settings\StorePaymentMethodRequest;
use App\Http\Requests\Finance\Settings\UpdatePaymentMethodRequest;
use App\Models\Finance\Settings\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:admin.super');
    }

    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('name')->paginate(15);

        return view('finance.settings.payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('finance.settings.payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request)
    {
        PaymentMethod::create($request->validated());

        return redirect()->route('settings.payment-methods.index')
            ->with('success', 'Método de pagamento criado com sucesso!');
    }

    public function show(PaymentMethod $paymentMethod)
    {
        return view('finance.settings.payment-methods.show', compact('paymentMethod'));
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('finance.settings.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $paymentMethod->update($request->validated());

        return redirect()->route('settings.payment-methods.index')
            ->with('success', 'Método de pagamento atualizado com sucesso!');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->route('settings.payment-methods.index')
            ->with('success', 'Método de pagamento excluído com sucesso!');
    }
}
