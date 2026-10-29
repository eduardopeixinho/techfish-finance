<?php

namespace App\Models\Investment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'investment_id',
        'type',
        'quantity',
        'price',
        'total_amount',
        'fees',
        'net_amount',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:6',
        'total_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Inclui ativos excluídos (soft delete): o histórico continua exibindo o ticker
     * em vez de quebrar a listagem com relação nula.
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class)->withTrashed();
    }
}
