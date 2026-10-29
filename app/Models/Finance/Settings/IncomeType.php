<?php

namespace App\Models\Finance\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];
}
