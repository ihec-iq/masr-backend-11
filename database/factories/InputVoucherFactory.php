<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InputVoucher>
 */
class InputVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'input_voucher_state_id' => fake()->numberBetween(1, 3),
            'employee_id' => fake()->numberBetween(1, 10),
            'number' => fake()->numberBetween(100, 99999),
            'date' => fake()->dateTimeBetween($startDate = '-1 years', $endDate = 'now'),
            'notes' => fake()->sentence(), 
        ];
    }
}
