<?php

namespace App\Models\Investment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'investment_type_id',
        'ticker',
        'name',
        'description',
        'current_price',
        'current_quantity',
        'total_invested',
        'current_value',
        'profit_loss',
        'profit_loss_percentage',
        'is_active',
    ];

    protected $casts = [
        'current_price' => 'decimal:6',
        'current_quantity' => 'integer',
        'total_invested' => 'decimal:2',
        'current_value' => 'decimal:2',
        'profit_loss' => 'decimal:2',
        'profit_loss_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investmentType()
    {
        return $this->belongsTo(InvestmentType::class);
    }

    public function transactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function dividends()
    {
        return $this->hasMany(InvestmentDividend::class);
    }
}
