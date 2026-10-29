<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:admin.super');
    }

    public function index()
    {
        $accountTypesCount = AccountType::count();
        $incomeTypesCount = IncomeType::count();
        $paymentMethodsCount = PaymentMethod::count();
        $activePaymentMethodsCount = PaymentMethod::active()->count();

        return view('finance.settings.index', compact(
            'accountTypesCount',
            'incomeTypesCount',
            'paymentMethodsCount',
            'activePaymentMethodsCount'
        ));
    }
}
