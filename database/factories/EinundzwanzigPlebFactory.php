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

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'association_status' => \App\Enums\AssociationStatus::ACTIVE,
        ]);
    }

    public function boardMember(): static
    {
        return $this->state(fn (array $attributes) => [
            'npub' => config('einundzwanzig.config.current_board')[0],
            'association_status' => \App\Enums\AssociationStatus::HONORARY,
        ]);
    }

    public function withPaidCurrentYear(): static
    {
        return $this->afterCreating(function (\App\Models\EinundzwanzigPleb $pleb) {
            $pleb->paymentEvents()->create([
                'year' => date('Y'),
                'amount' => 21000,
                'paid' => true,
                'event_id' => 'test_event_'.fake()->sha256(),
            ]);
        });
    }
}
