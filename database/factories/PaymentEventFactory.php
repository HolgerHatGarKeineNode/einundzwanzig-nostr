<?php

namespace Database\Factories;

use App\Models\EinundzwanzigPleb;
use App\Models\PaymentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentEvent>
 */
class PaymentEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'einundzwanzig_pleb_id' => EinundzwanzigPleb::factory(),
            'year' => fake()->year(),
            'event_id' => fake()->uuid(),
            'amount' => 21000,
            'paid' => false,
            'btc_pay_invoice' => null,
        ];
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'paid' => true,
        ]);
    }

    public function withYear(int $year): self
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }
}
