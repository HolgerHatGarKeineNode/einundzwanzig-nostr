<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseEvent>
 */
class CourseEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->dateTimeBetween('+1 day', '+90 days');
        $to = (clone $from)->modify('+2 hours');

        return [
            'course_id' => Course::factory(),
            'venue_id' => Venue::factory(),
            'from' => $from,
            'to' => $to,
        ];
    }

    public function past(): static
    {
        return $this->state(function () {
            $from = fake()->dateTimeBetween('-90 days', '-1 day');

            return [
                'from' => $from,
                'to' => (clone $from)->modify('+2 hours'),
            ];
        });
    }
}
