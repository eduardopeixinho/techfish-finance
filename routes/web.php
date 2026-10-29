<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreditCardBillController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\FinanceController;
use App\Http\Controllers\Finance\Settings\AccountTypeController;
use App\Http\Controllers\Finance\Settings\IncomeTypeController;
use App\Http\Controllers\Finance\Settings\PaymentMethodController;
use App\Http\Controllers\Finance\SettingsController;
use App\Http\Controllers\Finance\TransactionController;
use App\Http\Controllers\Investment\InvestmentController;
use App\Http\Controllers\Investment\InvestmentDividendController;
use App\Http\Controllers\Investment\InvestmentTransactionController;
use App\Http\Controllers\Investment\InvestmentTypeController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Página inicial pública; quem já está logado vai direto para o painel.
Route::get('/', function () {
    return auth()->check() ? redirect()->route('finance.dashboard') : view('welcome');
});

Route::get('/login/redirect',
    [AuthController::class, 'redirectToGoogle'])
    ->name('google.login');

Route::get('/auth/google/callback',
    [AuthController::class, 'handleGoogleCallback'])
    ->name('google.callback');

// Login de demonstração (só com DEMO_MODE=true; ver config/demo.php)
Route::post('/login/demo', DemoLoginController::class)
    ->middleware(['guest', 'throttle:10,1'])
    ->name('demo.login');

// Destino padrão pós-login (Breeze/Google usam a rota "dashboard"): o painel financeiro.
Route::redirect('/dashboard', '/finance/dashboard')->name('dashboard');

// Preferências de UI (toggle de visibilidade de valores, etc.)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/preferences/toggle-values', [PreferenceController::class, 'toggleValues'])
        ->name('preferences.toggle-values');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'view'])->name('profile.view');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rotas do Sistema Financeiro
    Route::redirect('/finance', '/finance/dashboard');
    Route::get('/finance/dashboard', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('/finance/reports', [FinanceController::class, 'reports'])->name('finance.reports');
    Route::get('/finance/reports/export-csv', [FinanceController::class, 'exportReportCSV'])->name('finance.reports.export-csv');

    // Rotas de Transações
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::resource('transactions', TransactionController::class);
        Route::resource('accounts', AccountController::class);
        Route::post('categories/defaults', [CategoryController::class, 'storeDefaults'])->name('categories.defaults');
        Route::resource('categories', CategoryController::class);

        // Rotas de Faturas de Cartão de Crédito
        Route::get('bills', [CreditCardBillController::class, 'index'])->name('bills.index');
        // billIdentifier tem barra ("06/2025"): aceita o "/" no último segmento
        Route::get('bills/{account}/{billIdentifier}', [CreditCardBillController::class, 'show'])
            ->where('billIdentifier', '.*')->name('bills.show');
        Route::get('api/bills/{account}/{billIdentifier}', [CreditCardBillController::class, 'apiSummary'])
            ->where('billIdentifier', '.*')->name('bills.api.summary');
    });

    // Rotas de Configurações
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::resource('account-types', AccountTypeController::class);
        Route::resource('income-types', IncomeTypeController::class);
        Route::resource('payment-methods', PaymentMethodController::class);
    });

    // Rotas de Administração - Gerenciamento de Perfis e Permissões
    Route::prefix('admin')->name('admin.')->group(function () {
        // Gestão de Roles
        Route::resource('roles', RoleController::class);
        Route::patch('roles/{role}/toggle', [RoleController::class, 'toggle'])->name('roles.toggle');

        // Gestão de Permissões
        Route::resource('permissions', PermissionController::class);
        Route::patch('permissions/{permission}/toggle', [PermissionController::class, 'toggle'])->name('permissions.toggle');

        // Gestão de Usuários e Roles
        Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
        Route::get('user-roles/{user}', [UserRoleController::class, 'show'])->name('user-roles.show');
        Route::get('user-roles/{user}/edit', [UserRoleController::class, 'edit'])->name('user-roles.edit');
        Route::patch('user-roles/{user}', [UserRoleController::class, 'update'])->name('user-roles.update');
        Route::delete('user-roles/{user}', [UserRoleController::class, 'destroy'])->name('user-roles.destroy');
        Route::post('user-roles/{user}/assign', [UserRoleController::class, 'assignRole'])->name('user-roles.assign');
        Route::delete('user-roles/{user}/remove', [UserRoleController::class, 'removeRole'])->name('user-roles.remove');
        Route::patch('user-roles/{user}/deactivate', [UserRoleController::class, 'deactivate'])->name('user-roles.deactivate');
        Route::patch('user-roles/{user}/activate', [UserRoleController::class, 'activate'])->name('user-roles.activate');
    });

    // Rotas do Sistema de Investimentos
    Route::get('/investment', [InvestmentController::class, 'dashboard'])->name('investment.dashboard');

    Route::prefix('investment')->name('investment.')->group(function () {
        Route::resource('investments', InvestmentController::class);
        Route::resource('types', InvestmentTypeController::class);
        Route::resource('transactions', InvestmentTransactionController::class);
        Route::resource('dividends', InvestmentDividendController::class);

        // Rotas para relatórios
        Route::get('/reports', [InvestmentController::class, 'reports'])->name('reports');
        Route::get('/reports/portfolio', [InvestmentController::class, 'portfolioReport'])->name('reports.portfolio');
        Route::get('/reports/dividends', [InvestmentDividendController::class, 'reports'])->name('reports.dividends');
    });
});

require __DIR__.'/auth.php';
