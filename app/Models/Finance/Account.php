<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tipos de conta aceitos (mesmos valores do `in:` dos Form Requests) → rótulo pt-BR.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'checking' => 'Conta corrente',
        'savings' => 'Poupança',
        'credit_card' => 'Cartão de crédito',
        'cash' => 'Dinheiro',
        'investment' => 'Investimento',
        'other' => 'Outros',
    ];

    protected $fillable = [
        'name',
        'bank',
        'type',
        'initial_balance',
        'current_balance',
        'currency',
        'color',
        'description',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transaction::class, 'transfer_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function updateBalance()
    {
        // OTIMIZADO: 1 query com conditional aggregation ao invés de 4 queries separadas
        $result = Transaction::where('account_id', $this->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expenses,
                COALESCE(SUM(CASE WHEN type = 'transfer' THEN amount ELSE 0 END), 0) as transfers_out
            ")
            ->first();

        // Transferências recebidas (esta conta é destino)
        $transfersIn = Transaction::where('transfer_account_id', $this->id)
            ->where('type', 'transfer')
            ->sum('amount');

        $this->current_balance = $this->initial_balance
            + (float) $result->income
            - (float) $result->expenses
            + (float) $transfersIn
            - (float) $result->transfers_out;

        $this->save();

        return $this->current_balance;
    }

    /**
     * Rótulo do tipo em pt-BR (ex.: "Cartão de crédito").
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function getBalanceAttribute()
    {
        return $this->current_balance;
    }
}
