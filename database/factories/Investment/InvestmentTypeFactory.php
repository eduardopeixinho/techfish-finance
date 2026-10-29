<?php

namespace Database\Factories\Investment;

use App\Models\Investment\InvestmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentTypeFactory extends Factory
{
    protected $model = InvestmentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Ação', 'FII', 'BDR', 'ETF', 'Renda Fixa']),
            'description' => fake()->sentence(),
            'has_dividends' => fake()->boolean(),
            'code' => fake()->unique()->lexify('????'),
            'color' => fake()->hexColor(),
        ];
    }
}
