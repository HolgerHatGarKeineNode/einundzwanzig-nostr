<?php

namespace Database\Factories;

use App\Models\Meetup;
use App\Models\MeetupEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetupEvent>
 */
class MeetupEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meetup_id' => Meetup::factory(),
            'start' => fake()->dateTimeBetween('+1 day', '+90 days'),
            'location' => fake()->company().', '.fake()->streetAddress(),
            'description' => fake()->paragraph(),
            'link' => fake()->url(),
            'attendees' => [
                'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
                bin2hex(random_bytes(32)),
            ],
            'might_attendees' => [bin2hex(random_bytes(32))],
            'nostr_status' => 'Sent event '.bin2hex(random_bytes(8)).' to wss://simple-relay.codingarena.top',
        ];
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'start' => fake()->dateTimeBetween('-90 days', '-1 day'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'start' => fake()->dateTimeBetween('+1 day', '+30 days'),
        ]);
    }
}
