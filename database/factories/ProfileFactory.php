<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->userName();

        return [
            'pubkey' => bin2hex(random_bytes(32)),
            'name' => $name,
            'display_name' => fake()->name(),
            'picture' => 'https://image.nostr.build/'.fake()->uuid().'.jpg',
            'banner' => null,
            'website' => fake()->url(),
            'about' => fake()->sentence(12),
            'nip05' => $name.'@einundzwanzig.space',
            'lud16' => $name.'@walletofsatoshi.com',
            'lud06' => null,
            'deleted' => false,
        ];
    }

    public function markusTurm(): static
    {
        return $this->state(fn (array $attributes) => [
            'pubkey' => 'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
            'name' => 'markusturm',
            'display_name' => 'Markus Turm',
            'picture' => 'https://m.primal.net/HQqf.jpg',
            'banner' => 'https://m.primal.net/HQqg.jpg',
            'website' => 'https://einundzwanzig.space',
            'about' => '#Bitcoin | Austrian Economics | Laissez-Faire Radical | @_einundzwanzig_',
            'nip05' => 'markusturm@einundzwanzig.space',
            'lud16' => 'markusturm@walletofsatoshi.com',
            'deleted' => false,
        ]);
    }
}
