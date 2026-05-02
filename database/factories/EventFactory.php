<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventId = bin2hex(random_bytes(32));
        $pubkey = bin2hex(random_bytes(32));
        $createdAt = fake()->dateTimeBetween('-1 year')->getTimestamp();

        return [
            'event_id' => $eventId,
            'parent_event_id' => null,
            'pubkey' => $pubkey,
            'type' => fake()->randomElement(['note', 'long-form', 'reaction']),
            'json' => json_encode([
                'id' => $eventId,
                'pubkey' => $pubkey,
                'created_at' => $createdAt,
                'kind' => 1,
                'tags' => [],
                'content' => fake()->paragraph(),
                'sig' => bin2hex(random_bytes(64)),
            ], JSON_THROW_ON_ERROR),
        ];
    }

    public function fromMarkusTurm(): static
    {
        return $this->state(function () {
            $eventId = bin2hex(random_bytes(32));
            $pubkey = 'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba';
            $createdAt = fake()->dateTimeBetween('-30 days')->getTimestamp();

            return [
                'event_id' => $eventId,
                'pubkey' => $pubkey,
                'type' => 'note',
                'json' => json_encode([
                    'id' => $eventId,
                    'pubkey' => $pubkey,
                    'created_at' => $createdAt,
                    'kind' => 1,
                    'tags' => [['t', 'bitcoin'], ['t', 'einundzwanzig']],
                    'content' => 'Bitcoin fixes this. #bitcoin #einundzwanzig',
                    'sig' => bin2hex(random_bytes(64)),
                ], JSON_THROW_ON_ERROR),
            ];
        });
    }
}
