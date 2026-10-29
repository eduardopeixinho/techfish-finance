<?php

namespace Database\Factories\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentDividend>
 */
class InvestmentDividendFactory extends Factory
{
    protected $model = InvestmentDividend::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 10, 1000);
        $taxRate = $this->faker->randomFloat(2, 0, 0.15);
        $taxWithheld = $amount * $taxRate;
        $netAmount = $amount - $taxWithheld;
        $quantityOwned = $this->faker->numberBetween(10, 1000);
        $amountPerShare = $amount / $quantityOwned;

        return [
            'user_id' => User::factory(),
            'investment_id' => Investment::factory(),
            'type' => $this->faker->randomElement(['dividend', 'jscp', 'rent']),
            'amount' => $amount,
            'amount_per_share' => $amountPerShare,
            'quantity_owned' => $quantityOwned,
            'record_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'payment_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'tax_withheld' => $taxWithheld,
            'net_amount' => $netAmount,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
