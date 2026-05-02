<?php

namespace Database\Factories;

use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'einundzwanzig_pleb_id' => EinundzwanzigPleb::factory(),
            'project_proposal_id' => ProjectProposal::factory(),
            'value' => fake()->boolean(70),
            'reason' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function approve(): static
    {
        return $this->state(fn () => ['value' => true]);
    }

    public function reject(): static
    {
        return $this->state(fn () => ['value' => false]);
    }
}
