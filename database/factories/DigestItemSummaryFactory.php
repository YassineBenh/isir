<?php

namespace Database\Factories;

use App\Models\Digest;
use App\Models\SourceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DigestItemSummary>
 */
class DigestItemSummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'digest_id' => Digest::factory(),
            'source_item_id' => SourceItem::factory(),
            'summary_markdown' => null,
            'summary_json' => null,
            'provider' => null,
            'model' => null,
            'status' => 'pending',
            'error' => null,
        ];
    }

    /**
     * Indicate that the summary was completed successfully.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'provider' => 'openai',
            'model' => 'gpt-4',
            'summary_markdown' => "## Key Changes\n\n- Added new feature X\n- Improved performance of Y\n\n## Breaking Changes\n\n- Removed deprecated method Z",
            'summary_json' => [
                'key_changes' => ['Added new feature X', 'Improved performance of Y'],
                'breaking_changes' => ['Removed deprecated method Z'],
                'highlights' => ['50% faster startup time'],
                'action_items' => ['Update config file for new feature X'],
            ],
        ]);
    }

    /**
     * Indicate that the summary generation failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'provider' => 'openai',
            'model' => 'gpt-4',
            'error' => 'API rate limit exceeded',
        ]);
    }

    /**
     * Indicate that the summary uses Anthropic.
     */
    public function anthropic(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'anthropic',
            'model' => 'claude-3-sonnet',
        ]);
    }
}
