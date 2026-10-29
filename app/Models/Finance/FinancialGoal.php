<?php

namespace App\Models\Finance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'target_amount',
        'current_amount',
        'start_date',
        'target_date',
        'status',
        'category_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Métodos auxiliares
    public function getProgressPercentage()
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return min(($this->current_amount / $this->target_amount) * 100, 100);
    }

    public function getRemainingAmount()
    {
        return max($this->target_amount - $this->current_amount, 0);
    }

    public function getDaysRemaining()
    {
        return Carbon::now()->diffInDays($this->target_date, false);
    }

    public function isOverdue()
    {
        return Carbon::now()->isAfter($this->target_date) && $this->status !== 'completed';
    }

    public function updateProgress()
    {
        // Atualizar progresso baseado nas transações
        if ($this->category_id) {
            $transactions = Transaction::where('user_id', $this->user_id)
                ->where('category_id', $this->category_id)
                ->where('type', $this->type)
                ->whereBetween('transaction_date', [$this->start_date, Carbon::now()])
                ->sum('amount');

            $this->current_amount = $transactions;

            // Marcar como completa se atingiu a meta
            if ($this->current_amount >= $this->target_amount) {
                $this->status = 'completed';
            }

            $this->save();
        }
    }
}
