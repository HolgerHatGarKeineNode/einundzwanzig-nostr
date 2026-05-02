<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\RenderedEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RenderedEvent>
 */
class RenderedEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->create()->event_id,
            'html' => '<p>'.fake()->paragraph().'</p>',
            'profile_image' => 'https://m.primal.net/'.fake()->uuid().'.jpg',
            'profile_name' => fake()->userName(),
        ];
    }
}
