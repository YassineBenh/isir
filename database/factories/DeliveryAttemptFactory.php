<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\DigestRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryAttempt>
 */
class DeliveryAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'digest_run_id' => DigestRun::factory(),
            'destination_id' => Destination::factory(),
            'status' => 'pending',
            'sent_at' => null,
            'provider_message_id' => null,
            'error' => null,
        ];
    }

    /**
     * Indicate that the delivery was sent successfully.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
            'provider_message_id' => fake()->uuid(),
        ]);
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error' => 'Webhook returned 500: Internal Server Error',
        ]);
    }
}
