<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'language_codes' => ['de'],
        ];
    }

    public function germany(): static
    {
        return $this->state(fn () => [
            'name' => 'Deutschland',
            'code' => 'DE',
            'language_codes' => ['de'],
        ]);
    }

    public function austria(): static
    {
        return $this->state(fn () => [
            'name' => 'Österreich',
            'code' => 'AT',
            'language_codes' => ['de'],
        ]);
    }

    public function switzerland(): static
    {
        return $this->state(fn () => [
            'name' => 'Schweiz',
            'code' => 'CH',
            'language_codes' => ['de', 'fr', 'it'],
        ]);
    }
}
