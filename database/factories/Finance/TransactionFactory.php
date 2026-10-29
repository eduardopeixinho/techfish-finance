<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'type' => fake()->randomElement(['income', 'expense']),
            'transaction_date' => fake()->date(),
            'account_id' => Account::factory(),
            'category_id' => null,
            'notes' => fake()->optional()->sentence(),
            'payment_method' => fake()->randomElement(['pix', 'credit_card', 'debit_card', 'cash', 'bank_transfer']),
            'status' => 'completed',
            'user_id' => User::factory(),
        ];
    }
}
