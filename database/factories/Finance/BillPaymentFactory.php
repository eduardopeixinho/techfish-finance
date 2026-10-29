<?php

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\BillPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillPayment>
 */
class BillPaymentFactory extends Factory
{
    /**
     * Fatura marcada como paga (sem transferência).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory()->state(['type' => 'credit_card', 'closing_day' => 1, 'due_day' => 10]),
            'bill_identifier' => now()->format('m-Y'),
            'transaction_id' => null,
            'amount' => fake()->randomFloat(2, 50, 3000),
            'paid_at' => now()->toDateString(),
        ];
    }
}
