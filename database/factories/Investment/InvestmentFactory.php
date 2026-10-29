<?php

namespace Database\Factories\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentFactory extends Factory
{
    protected $model = Investment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'investment_type_id' => InvestmentType::factory(),
            'ticker' => fake()->unique()->lexify('????'),
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'current_price' => fake()->randomFloat(2, 1, 500),
            'current_quantity' => fake()->numberBetween(1, 1000),
            'total_invested' => fake()->randomFloat(2, 100, 50000),
            'current_value' => fake()->randomFloat(2, 100, 50000),
            'profit_loss' => fake()->randomFloat(2, -5000, 5000),
            'profit_loss_percentage' => fake()->randomFloat(2, -20, 20),
            'is_active' => true,
        ];
    }
}
