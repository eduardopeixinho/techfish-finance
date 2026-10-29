<?php

namespace App\Models\Investment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvestmentDividend extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tipos de provento (mesmos valores do `in:` do Form Request) → rótulo pt-BR.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'dividend' => 'Dividendo',
        'jscp' => 'JCP',
        'rent' => 'Aluguel (FII)',
        'bonus' => 'Bonificação',
        'split' => 'Desdobramento',
        'grouping' => 'Grupamento',
    ];

    protected $fillable = [
        'user_id',
        'investment_id',
        'type',
        'amount',
        'amount_per_share',
        'quantity_owned',
        'record_date',
        'payment_date',
        'tax_withheld',
        'net_amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_per_share' => 'decimal:6',
        'quantity_owned' => 'integer',
        'record_date' => 'date',
        'payment_date' => 'date',
        'tax_withheld' => 'decimal:2',
        'net_amount' => 'decimal:2',
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

    /**
     * Accessor para valor bruto (mesmo que amount)
     */
    public function getGrossAmountAttribute()
    {
        return $this->amount;
    }

    /**
     * Accessor para o nome do tipo de provento
     */
    public function getTypeNameAttribute()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
