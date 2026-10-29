<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'google_id',
        'nickname',
        'name',
        'avatar',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'deactivated_at' => 'datetime',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function financialCategories()
    {
        return $this->hasMany(FinancialCategory::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTotalBalance()
    {
        return $this->accounts()->where('is_active', true)->sum('current_balance');
    }

    public function getMonthlyIncome($month = null, $year = null)
    {
        $query = $this->transactions()->where('type', 'income');

        if ($month && $year) {
            $query->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year);
        }

        return $query->sum('amount');
    }

    public function getMonthlyExpenses($month = null, $year = null)
    {
        $query = $this->transactions()->where('type', 'expense');

        if ($month && $year) {
            $query->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year);
        }

        return $query->sum('amount');
    }

    /**
     * Obtém resumo financeiro em uma única query (performance).
     * Retorna totalBalance, monthlyIncome e monthlyExpenses.
     *
     * @param  int|null  $month  Mês (1-12), null = todos
     * @param  int|null  $year  Ano, null = todos
     * @return array{totalBalance: float, monthlyIncome: float, monthlyExpenses: float}
     */
    public function getFinancialSummary(?int $month = null, ?int $year = null): array
    {
        // Saldo total (1 query)
        $totalBalance = (float) $this->accounts()
            ->where('is_active', true)
            ->sum('current_balance');

        // Receitas e despesas do mês em 1 query com conditional aggregation
        $query = $this->transactions()
            ->whereIn('type', ['income', 'expense']);

        if ($month && $year) {
            $query->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year);
        }

        $result = $query->selectRaw("
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as monthly_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as monthly_expenses
        ")->first();

        return [
            'totalBalance' => $totalBalance,
            'monthlyIncome' => (float) ($result->monthly_income ?? 0),
            'monthlyExpenses' => (float) ($result->monthly_expenses ?? 0),
        ];
    }

    /**
     * Relacionamento com roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Verifica se o usuário tem um role específico
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Verifica se o usuário tem qualquer um dos roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Verifica se o usuário tem uma permissão específica
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('slug', $permission);
        })->exists();
    }

    /**
     * Verifica se o usuário tem qualquer uma das permissões especificadas
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('slug', $permissions);
        })->exists();
    }

    /**
     * Atribui um role ao usuário
     */
    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching($role);
    }

    /**
     * Remove um role do usuário
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role);
    }

    /**
     * Sincroniza os roles do usuário
     */
    public function syncRoles(array $roles): void
    {
        $this->roles()->sync($roles);
    }

    /**
     * Desativado pelo admin: não entra no app (login, sessão e tokens de API são recusados).
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Conta de demonstração (config/demo.php): compartilhada, não pode ser editada nem excluída.
     */
    public function isDemo(): bool
    {
        return $this->email === config('demo.email');
    }

    /**
     * Verifica se é administrador
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Verifica se pode acessar o módulo financeiro
     */
    public function canAccessFinance(): bool
    {
        return $this->hasAnyPermission(['finance.read', 'finance.manage']);
    }

    // ======= RELACIONAMENTOS DE INVESTIMENTOS =======

    /**
     * Investimentos do usuário
     */
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * Transações de investimento do usuário
     */
    public function investmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    /**
     * Dividendos/proventos do usuário
     */
    public function investmentDividends()
    {
        return $this->hasMany(InvestmentDividend::class);
    }

    /**
     * Verifica se pode acessar o módulo de investimentos
     */
    public function canAccessInvestment(): bool
    {
        return $this->hasAnyPermission(['investment.read', 'investment.manage']);
    }
}
