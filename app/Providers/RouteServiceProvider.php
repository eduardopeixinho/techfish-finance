<?php

namespace App\Providers;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Investment\InvestmentTransaction;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Model bindings
        Route::model('category', FinancialCategory::class);
        Route::model('accountType', AccountType::class);
        Route::model('incomeType', IncomeType::class);
        Route::model('paymentMethod', PaymentMethod::class);
        Route::model('account', Account::class);

        // Bindings específicos por contexto
        Route::bind('transaction', function ($value, $route) {
            // Se a rota está no contexto de investimentos, usar o model de investimento
            if (str_starts_with($route->getName(), 'investment.transactions.')) {
                return InvestmentTransaction::findOrFail($value);
            }

            // Caso contrário, usar o model de finanças
            return Transaction::findOrFail($value);
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
