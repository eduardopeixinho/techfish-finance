<?php

namespace App\Models\Investment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'has_dividends',
        'color',
    ];

    protected $casts = [
        'has_dividends' => 'boolean',
    ];

    public function investments()
    {
        return $this->hasMany(Investment::class);
    }
}
