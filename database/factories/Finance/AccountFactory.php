<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'type' => fake()->randomElement(['checking', 'savings', 'credit_card', 'cash']),
            'initial_balance' => fake()->randomFloat(2, 0, 10000),
            'current_balance' => fake()->randomFloat(2, 0, 10000),
            'currency' => 'BRL',
            'color' => fake()->hexColor(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'user_id' => User::factory(),
        ];
    }
}
