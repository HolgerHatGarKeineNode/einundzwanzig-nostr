<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Meetup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meetup>
 */
class MeetupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Einundzwanzig '.fake()->city();

        return [
            'city_id' => City::factory(),
            'name' => $name,
            'description' => fake()->paragraph(),
            'website' => 'https://einundzwanzig.space/meetups/'.str()->slug($name),
            'nostr_pubkey' => bin2hex(random_bytes(32)),
            'github_data' => [
                'repo' => 'einundzwanzig-portal',
                'path' => 'meetups/'.str()->slug($name).'.json',
            ],
            'simplified_geojson' => null,
        ];
    }

    public function vienna(): static
    {
        return $this->state(fn () => [
            'name' => 'Einundzwanzig Wien',
            'description' => 'Das Bitcoin-only Meetup in Wien. Jeden ersten Donnerstag im Monat.',
            'website' => 'https://einundzwanzig.space/meetups/wien',
            'nostr_pubkey' => 'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
        ]);
    }

    public function berlin(): static
    {
        return $this->state(fn () => [
            'name' => 'Einundzwanzig Berlin',
            'description' => 'Bitcoin Meetup in der Hauptstadt. Plebs willkommen.',
            'website' => 'https://einundzwanzig.space/meetups/berlin',
        ]);
    }
}
