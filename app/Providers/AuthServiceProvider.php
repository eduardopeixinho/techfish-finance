<?php

namespace App\Providers;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Settings\AccountType;
use App\Models\Finance\Settings\IncomeType;
use App\Models\Finance\Settings\PaymentMethod;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Models\User;
use App\Policies\Finance\AccountPolicy;
use App\Policies\Finance\FinancialCategoryPolicy;
use App\Policies\Finance\Settings\AccountTypePolicy;
use App\Policies\Finance\Settings\IncomeTypePolicy;
use App\Policies\Finance\Settings\PaymentMethodPolicy;
use App\Policies\Finance\TransactionPolicy;
use App\Policies\InvestmentDividendPolicy;
use App\Policies\InvestmentPolicy;
use App\Policies\InvestmentTransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Account::class => AccountPolicy::class,
        Transaction::class => TransactionPolicy::class,
        FinancialCategory::class => FinancialCategoryPolicy::class,
        AccountType::class => AccountTypePolicy::class,
        IncomeType::class => IncomeTypePolicy::class,
        PaymentMethod::class => PaymentMethodPolicy::class,
        Investment::class => InvestmentPolicy::class,
        InvestmentDividend::class => InvestmentDividendPolicy::class,
        InvestmentTransaction::class => InvestmentTransactionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate para acesso ao módulo de administração
        Gate::define('admin.access', function ($user) {
            return $user->isAdmin() || $user->hasAnyPermission([
                'admin.users',
                'admin.roles',
                'admin.permissions',
            ]);
        });

        // Gate para operações administrativas críticas (criar/editar/deletar)
        Gate::define('admin.super', function ($user) {
            return $user->isAdmin();
        });

        // Desativar/reativar usuário: só admin, nunca a si mesmo nem outro admin
        // (para desativar um admin, tire antes o perfil de admin dele).
        Gate::define('admin.users.deactivate', function (User $actor, User $target) {
            return $actor->isAdmin() && $actor->isNot($target) && ! $target->isAdmin();
        });

        // Tokens de API (Sanctum) de usuário desativado são recusados.
        Sanctum::authenticateAccessTokensUsing(
            fn ($accessToken, bool $isValid) => $isValid && ! $accessToken->tokenable?->isDeactivated()
        );
    }
}
