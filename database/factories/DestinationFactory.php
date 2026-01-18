<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destination>
 */
class DestinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'email',
            'name' => 'Email Notifications',
            'config' => [
                'email' => fake()->safeEmail(),
            ],
            'is_enabled' => true,
        ];
    }

    /**
     * Indicate that the destination is a Slack webhook.
     */
    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'slack',
            'name' => '#'.fake()->slug(2),
            'config' => [
                'webhook_url' => 'https://hooks.slack.com/services/'.fake()->regexify('[A-Z0-9]{9}/[A-Z0-9]{11}/[A-Za-z0-9]{24}'),
            ],
        ]);
    }

    /**
     * Indicate that the destination is a Discord webhook.
     */
    public function discord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'discord',
            'name' => '#'.fake()->slug(2),
            'config' => [
                'webhook_url' => 'https://discord.com/api/webhooks/'.fake()->numerify('##################').'/'.fake()->regexify('[A-Za-z0-9_-]{68}'),
            ],
        ]);
    }

    /**
     * Indicate that the destination is an email.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'email',
            'name' => 'Email Notifications',
            'config' => [
                'email' => fake()->safeEmail(),
            ],
        ]);
    }

    /**
     * Indicate that the destination is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
