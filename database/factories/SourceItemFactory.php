<?php

namespace Database\Factories;

use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SourceItem>
 */
class SourceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $version = fake()->semver();

        return [
            'source_id' => Source::factory(),
            'external_id' => (string) fake()->unique()->randomNumber(8),
            'title' => "v{$version}",
            'url' => fake()->url(),
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'raw_content' => fake()->paragraphs(3, true),
            'metadata' => [
                'tag_name' => "v{$version}",
                'prerelease' => false,
                'draft' => false,
            ],
        ];
    }

    /**
     * Indicate that the item is a prerelease.
     */
    public function prerelease(): static
    {
        return $this->state(function (array $attributes) {
            $version = fake()->semver().'-beta.'.fake()->randomDigit();

            return [
                'title' => "v{$version}",
                'metadata' => [
                    'tag_name' => "v{$version}",
                    'prerelease' => true,
                    'draft' => false,
                ],
            ];
        });
    }

    /**
     * Indicate that the item is an RSS entry.
     */
    public function rssEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_id' => fake()->uuid(),
            'title' => fake()->sentence(),
            'metadata' => [
                'author' => fake()->name(),
                'categories' => fake()->words(3),
            ],
        ]);
    }

    /**
     * Indicate that the item is a YouTube video.
     */
    public function youtubeVideo(): static
    {
        return $this->state(fn (array $attributes) => [
            'external_id' => fake()->regexify('[A-Za-z0-9_-]{11}'),
            'title' => fake()->sentence(),
            'url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'metadata' => [
                'duration' => fake()->numberBetween(60, 3600),
                'view_count' => fake()->numberBetween(100, 1000000),
            ],
        ]);
    }
}
