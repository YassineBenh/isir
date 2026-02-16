<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Digest>
 */
class DigestFactory extends Factory
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
            'name' => fake()->randomElement(['Backend Stack', 'Frontend Tools', 'Security Updates', 'Personal Projects']),
            'frequency' => fake()->randomElement(['daily', 'weekly']),
            'timezone' => fake()->timezone(),
            'send_time' => fake()->time('H:i:s'),
            'send_day_of_week' => null,
            'is_enabled' => true,
            'last_successful_run_at' => null,
            'last_dispatched_at' => null,
            'ai_enabled' => true,
            'include_versions_summary' => false,
            'ai_prefs' => null,
        ];
    }

    /**
     * Indicate that the digest is weekly.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'send_day_of_week' => fake()->numberBetween(0, 6),
        ]);
    }

    /**
     * Indicate that the digest is daily.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
            'send_day_of_week' => null,
        ]);
    }

    /**
     * Indicate that the digest is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Indicate that the digest has AI disabled.
     */
    public function withoutAi(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_enabled' => false,
        ]);
    }

    /**
     * Indicate that the digest has custom AI preferences.
     */
    public function withAiPrefs(array $prefs = []): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_prefs' => array_merge([
                'model' => 'gpt-4',
                'prompt' => 'Summarize focusing on breaking changes.',
            ], $prefs),
        ]);
    }
}
