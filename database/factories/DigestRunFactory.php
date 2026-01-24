<?php

namespace Database\Factories;

use App\Models\Digest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DigestRun>
 */
class DigestRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodEnd = fake()->dateTimeBetween('-1 week', 'now');
        $periodStart = (clone $periodEnd)->modify('-1 day');

        return [
            'digest_id' => Digest::factory(),
            'period_start_at' => $periodStart,
            'period_end_at' => $periodEnd,
            'status' => 'pending',
            'ai_summary' => null,
            'started_at' => null,
            'finished_at' => null,
            'error' => null,
        ];
    }

    /**
     * Indicate that the run is in progress.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the run completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'ai_summary' => "Here's a summary of your updates:\n\n**Laravel Framework** - Version 11.0.0 brings new features and bug fixes.",
        ]);
    }

    /**
     * Indicate that the run failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'error' => 'Failed to fetch releases: API rate limit exceeded',
        ]);
    }

    /**
     * Indicate that this is a weekly run.
     */
    public function weekly(): static
    {
        return $this->state(function (array $attributes) {
            $periodEnd = fake()->dateTimeBetween('-1 week', 'now');
            $periodStart = (clone $periodEnd)->modify('-1 week');

            return [
                'period_start_at' => $periodStart,
                'period_end_at' => $periodEnd,
            ];
        });
    }
}
