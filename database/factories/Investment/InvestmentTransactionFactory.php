<?php

namespace Database\Factories\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentTransactionFactory extends Factory
{
    protected $model = InvestmentTransaction::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 100);
        $price = fake()->randomFloat(4, 1, 500);
        $fees = fake()->randomFloat(2, 0, 50);

        return [
            'user_id' => User::factory(),
            'investment_id' => Investment::factory(),
            'type' => fake()->randomElement(['buy', 'sell']),
            'quantity' => $quantity,
            'price' => $price,
            'total_amount' => round($quantity * $price, 2),
            'fees' => $fees,
            'net_amount' => round($quantity * $price - $fees, 2),
            'transaction_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
