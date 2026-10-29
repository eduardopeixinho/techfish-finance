<?php

namespace App\Models\Finance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tipos de transação → rótulo pt-BR.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'income' => 'Receita',
        'expense' => 'Despesa',
        'transfer' => 'Transferência',
    ];

    protected $fillable = [
        'description',
        'amount',
        'type',
        'transaction_date',
        'competency_date',
        'due_date',
        'bill_identifier',
        'account_id',
        'category_id',
        'transfer_account_id',
        'notes',
        'payment_method',
        'status',
        'tags',
        'receipt_path',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'competency_date' => 'date',
        'due_date' => 'date',
        'tags' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'transfer_account_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', Carbon::now()->month)
            ->whereYear('transaction_date', Carbon::now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('transaction_date', Carbon::now()->year);
    }

    /**
     * Rótulo do tipo em pt-BR (Receita / Despesa / Transferência).
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    public function getFormattedAmountAttribute()
    {
        return 'R$ '.number_format($this->amount, 2, ',', '.');
    }

    public function getFormattedDateAttribute()
    {
        return $this->transaction_date->format('d/m/Y');
    }
}
