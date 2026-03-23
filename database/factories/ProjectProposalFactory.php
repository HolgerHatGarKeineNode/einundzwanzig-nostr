<?php

namespace Database\Factories;

use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectProposal>
 */
class ProjectProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'einundzwanzig_pleb_id' => EinundzwanzigPleb::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'support_in_sats' => $this->faker->numberBetween(10000, 1000000),
            'website' => $this->faker->url(),
        ];
    }
}
