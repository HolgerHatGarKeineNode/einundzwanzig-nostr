<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectProposal>
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
            'einundzwanzig_pleb_id' => \App\Models\EinundzwanzigPleb::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'support_in_sats' => $this->faker->numberBetween(10000, 1000000),
        ];
    }
}
