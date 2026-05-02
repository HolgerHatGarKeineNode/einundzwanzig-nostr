<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Bitcoin Basics',
            'Self Custody und Hardware Wallets',
            'Lightning Network 101',
            'Nostr Einführung',
            'Bitcoin für Unternehmer',
            'Privacy & Coinjoin',
            'Running Your Own Node',
        ];

        return [
            'lecturer_id' => Lecturer::factory(),
            'name' => fake()->randomElement($titles),
            'description' => fake()->paragraphs(2, true),
            'duration_minutes' => fake()->randomElement([60, 90, 120, 180]),
        ];
    }

    public function bitcoinBasics(): static
    {
        return $this->state(fn () => [
            'name' => 'Bitcoin Basics',
            'description' => 'Eine umfassende Einführung in Bitcoin: Was ist Bitcoin, wie funktioniert die Blockchain, warum ist es revolutionär. Perfekt für Einsteiger.',
            'duration_minutes' => 90,
        ]);
    }
}
