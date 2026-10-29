<?php

namespace Database\Factories\Finance\Settings;

use App\Models\Finance\Settings\IncomeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeType>
 */
class IncomeTypeFactory extends Factory
{
    protected $model = IncomeType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
