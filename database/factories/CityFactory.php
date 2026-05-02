<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->city(),
            'latitude' => fake()->latitude(47, 55),
            'longitude' => fake()->longitude(6, 16),
            'osm_relation' => null,
            'simplified_geojson' => null,
        ];
    }

    public function vienna(): static
    {
        return $this->state(fn () => [
            'name' => 'Wien',
            'latitude' => 48.2082,
            'longitude' => 16.3738,
        ]);
    }

    public function berlin(): static
    {
        return $this->state(fn () => [
            'name' => 'Berlin',
            'latitude' => 52.5200,
            'longitude' => 13.4050,
        ]);
    }

    public function munich(): static
    {
        return $this->state(fn () => [
            'name' => 'München',
            'latitude' => 48.1351,
            'longitude' => 11.5820,
        ]);
    }

    public function zurich(): static
    {
        return $this->state(fn () => [
            'name' => 'Zürich',
            'latitude' => 47.3769,
            'longitude' => 8.5417,
        ]);
    }
}
