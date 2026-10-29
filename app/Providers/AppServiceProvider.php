<?php

namespace App\Providers;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Observers\FinancialAuditObserver;
use App\Support\Money;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer de auditoria para dados financeiros
        Transaction::observe(FinancialAuditObserver::class);
        Account::observe(FinancialAuditObserver::class);
        Investment::observe(FinancialAuditObserver::class);
        InvestmentTransaction::observe(FinancialAuditObserver::class);
        InvestmentDividend::observe(FinancialAuditObserver::class);

        // $title é resolvido no próprio layout (layouts/finance e layouts/admin),
        // via \App\Support\PageTitle::forRoute(...).

        // $money é o formatador monetário que respeita a preferência
        // do usuário de ocultar valores. Injetado em todas as views
        // que renderizam números (dashboard, finance, investment, bills,
        // accounts, transactions, etc.).
        View::composer(
            [
                'layouts.*',
                'finance.*',
                'investment.*',
                'admin.*',
            ],
            function ($view) {
                $view->with('money', Money::fromSession());
            }
        );
    }
}
