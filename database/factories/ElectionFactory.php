<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Election>
 */
class ElectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => $this->faker->year(),
            'candidates' => [],
            'end_time' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }
}
