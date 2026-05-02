<?php

namespace Database\Factories;

use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'bio' => fake()->paragraph(),
            'pubkey' => bin2hex(random_bytes(32)),
            'website' => fake()->url(),
            'active' => true,
        ];
    }

    public function markusTurm(): static
    {
        return $this->state(fn () => [
            'name' => 'Markus Turm',
            'bio' => 'Hobby Hedge Fund Manager. Bitcoin, Austrian Economics, Laissez-Faire Radical.',
            'npub' => 'npub17fqtu2mgf7zueq2kdusgzwr2lqwhgfl2scjsez77ddag2qx8vxaq3vnr8y',
            'pubkey' => 'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
            'website' => 'https://einundzwanzig.space',
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
