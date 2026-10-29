<?php

namespace Database\Factories\Finance;

use App\Models\Finance\FinancialCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialCategoryFactory extends Factory
{
    protected $model = FinancialCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['income', 'expense']),
            'color' => fake()->hexColor(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'user_id' => User::factory(),
        ];
    }
}
