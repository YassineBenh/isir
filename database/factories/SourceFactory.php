<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = fake()->userName();
        $repo = fake()->slug(2);

        return [
            'type' => 'github_repo',
            'canonical_key' => "github:{$owner}/{$repo}",
            'name' => "{$owner}/{$repo}",
            'url' => "https://github.com/{$owner}/{$repo}",
            'config' => [
                'owner' => $owner,
                'repo' => $repo,
            ],
            'is_enabled' => true,
            'last_fetched_at' => null,
            'fetch_state' => null,
            'last_error' => null,
        ];
    }

    /**
     * Indicate that the source is an RSS feed.
     */
    public function rssFeed(): static
    {
        return $this->state(function (array $attributes) {
            $url = fake()->url();

            return [
                'type' => 'rss_feed',
                'canonical_key' => "rss:{$url}",
                'name' => fake()->domainName(),
                'url' => $url,
                'config' => [
                    'feed_url' => $url,
                ],
            ];
        });
    }

    /**
     * Indicate that the source is a YouTube channel.
     */
    public function youtubeChannel(): static
    {
        return $this->state(function (array $attributes) {
            $channelId = 'UC'.fake()->regexify('[A-Za-z0-9]{22}');

            return [
                'type' => 'youtube_channel',
                'canonical_key' => "youtube:{$channelId}",
                'name' => fake()->company().' Channel',
                'url' => "https://www.youtube.com/channel/{$channelId}",
                'config' => [
                    'channel_id' => $channelId,
                ],
            ];
        });
    }

    /**
     * Indicate that the source is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }

    /**
     * Indicate that the source has an error.
     */
    public function withError(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_error' => 'Failed to fetch: Connection timeout',
        ]);
    }
}
