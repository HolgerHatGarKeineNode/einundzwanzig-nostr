<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EinundzwanzigPleb>
 */
class EinundzwanzigPlebFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pubkey' => $this->faker->sha256(),
            'npub' => $this->faker->word(),
            'email' => $this->faker->safeEmail(),
            'association_status' => \App\Enums\AssociationStatus::DEFAULT,
        ];
    }
}
