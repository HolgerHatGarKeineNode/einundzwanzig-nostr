<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => fake()->randomElement(['Bitcoin Bar', 'Plebsfreundlicher Coworking', 'Hodler Hangout', 'Lightning Lounge']).' '.fake()->lastName(),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'website' => fake()->url(),
        ];
    }

    public function bitcoinBarVienna(): static
    {
        return $this->state(fn () => [
            'name' => 'Bitcoin Bar Wien',
            'description' => 'Die erste Bitcoin-only Bar im 7. Bezirk.',
            'address' => 'Neubaugasse 21, 1070 Wien',
            'website' => 'https://bitcoin-bar.at',
        ]);
    }
}
