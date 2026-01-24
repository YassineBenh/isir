<?php

namespace Database\Seeders;

use App\Models\Destination;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\Source;
use App\Models\SourceItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user with admin role
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('test@example.com'),
        ]);
        $user->assignRole('admin');

        // Create GitHub repo sources
        $coolify = Source::create([
            'type' => 'github_repo',
            'canonical_key' => 'github:coollabsio/coolify',
            'name' => 'coollabsio/coolify',
            'url' => 'https://github.com/coollabsio/coolify',
            'config' => ['owner' => 'coollabsio', 'repo' => 'coolify'],
            'is_enabled' => true,
            'last_fetched_at' => now()->subHours(2),
        ]);

        $laravelFramework = Source::create([
            'type' => 'github_repo',
            'canonical_key' => 'github:laravel/framework',
            'name' => 'laravel/framework',
            'url' => 'https://github.com/laravel/framework',
            'config' => ['owner' => 'laravel', 'repo' => 'framework'],
            'is_enabled' => true,
            'last_fetched_at' => now()->subHours(1),
        ]);

        $opencode = Source::create([
            'type' => 'github_repo',
            'canonical_key' => 'github:anomalyco/opencode',
            'name' => 'anomalyco/opencode',
            'url' => 'https://github.com/anomalyco/opencode',
            'config' => ['owner' => 'anomalyco', 'repo' => 'opencode'],
            'is_enabled' => true,
            'last_fetched_at' => now()->subMinutes(30),
        ]);

        // Create source items (releases) for each repo
        $this->createCoolifyReleases($coolify);
        $this->createLaravelReleases($laravelFramework);
        $this->createOpencodeReleases($opencode);

        // Create destinations
        $emailDestination = Destination::create([
            'user_id' => $user->id,
            'type' => 'email',
            'name' => 'Personal Email',
            'config' => ['email' => 'test@example.com'],
            'is_enabled' => true,
        ]);

        $slackDestination = Destination::create([
            'user_id' => $user->id,
            'type' => 'slack',
            'name' => '#dev-releases',
            'config' => ['webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX'],
            'is_enabled' => true,
        ]);

        $discordDestination = Destination::create([
            'user_id' => $user->id,
            'type' => 'discord',
            'name' => '#releases',
            'config' => ['webhook_url' => 'https://discord.com/api/webhooks/123456789/abcdefghijklmnop'],
            'is_enabled' => false,
        ]);

        // Create digests
        $dailyDigest = Digest::create([
            'user_id' => $user->id,
            'name' => 'Daily Dev Updates',
            'frequency' => 'daily',
            'timezone' => 'America/New_York',
            'send_time' => '09:00:00',
            'is_enabled' => true,
            'ai_enabled' => true,
            'ai_prefs' => ['model' => 'claude-3-5-sonnet', 'focus' => 'breaking_changes'],
        ]);

        $weeklyDigest = Digest::create([
            'user_id' => $user->id,
            'name' => 'Weekly Laravel Recap',
            'frequency' => 'weekly',
            'timezone' => 'UTC',
            'send_time' => '10:00:00',
            'send_day_of_week' => 1, // Monday
            'is_enabled' => true,
            'ai_enabled' => true,
        ]);

        $infrastructureDigest = Digest::create([
            'user_id' => $user->id,
            'name' => 'Infrastructure Alerts',
            'frequency' => 'daily',
            'timezone' => 'Europe/London',
            'send_time' => '08:00:00',
            'is_enabled' => false,
            'ai_enabled' => false,
        ]);

        // Attach sources to digests
        $dailyDigest->sources()->attach([$coolify->id, $laravelFramework->id, $opencode->id]);
        $weeklyDigest->sources()->attach([$laravelFramework->id]);
        $infrastructureDigest->sources()->attach([$coolify->id]);

        // Attach destinations to digests
        $dailyDigest->destinations()->attach([$emailDestination->id, $slackDestination->id]);
        $weeklyDigest->destinations()->attach([$emailDestination->id]);
        $infrastructureDigest->destinations()->attach([$discordDestination->id]);

        // Create digest runs
        $this->createDigestRuns($dailyDigest, $coolify, $laravelFramework, $opencode);
        $this->createDigestRuns($weeklyDigest, $laravelFramework);
    }

    private function createCoolifyReleases(Source $source): void
    {
        $releases = [
            [
                'tag' => 'v4.0.0-beta.406',
                'date' => '2025-01-20',
                'body' => "## What's Changed\n\n* Fix Docker Compose deployment issues\n* Improved resource monitoring dashboard\n* Added support for custom SSL certificates\n* Performance improvements for large deployments\n\n**Full Changelog**: https://github.com/coollabsio/coolify/compare/v4.0.0-beta.405...v4.0.0-beta.406",
                'prerelease' => true,
            ],
            [
                'tag' => 'v4.0.0-beta.405',
                'date' => '2025-01-18',
                'body' => "## What's Changed\n\n* Fixed webhook handling for GitLab\n* Updated dependencies\n* Bug fixes for environment variable handling\n\n**Full Changelog**: https://github.com/coollabsio/coolify/compare/v4.0.0-beta.404...v4.0.0-beta.405",
                'prerelease' => true,
            ],
            [
                'tag' => 'v4.0.0-beta.404',
                'date' => '2025-01-15',
                'body' => "## What's Changed\n\n* New backup system with S3 support\n* Improved database management UI\n* Fixed memory leaks in monitoring service\n\n**Full Changelog**: https://github.com/coollabsio/coolify/compare/v4.0.0-beta.403...v4.0.0-beta.404",
                'prerelease' => true,
            ],
            [
                'tag' => 'v4.0.0-beta.403',
                'date' => '2025-01-10',
                'body' => "## What's Changed\n\n* Added ARM64 support\n* Improved container health checks\n* UI/UX improvements\n\n**Full Changelog**: https://github.com/coollabsio/coolify/compare/v4.0.0-beta.402...v4.0.0-beta.403",
                'prerelease' => true,
            ],
            [
                'tag' => 'v4.0.0-beta.402',
                'date' => '2025-01-05',
                'body' => "## What's Changed\n\n* Fixed deployment rollback functionality\n* Added support for private Docker registries\n* Performance optimizations\n\n**Full Changelog**: https://github.com/coollabsio/coolify/compare/v4.0.0-beta.401...v4.0.0-beta.402",
                'prerelease' => true,
            ],
        ];

        foreach ($releases as $release) {
            SourceItem::create([
                'source_id' => $source->id,
                'external_id' => 'coolify-'.str_replace('.', '-', $release['tag']),
                'title' => $release['tag'],
                'url' => "https://github.com/coollabsio/coolify/releases/tag/{$release['tag']}",
                'published_at' => $release['date'],
                'raw_content' => $release['body'],
                'metadata' => [
                    'tag_name' => $release['tag'],
                    'prerelease' => $release['prerelease'],
                    'draft' => false,
                ],
            ]);
        }
    }

    private function createLaravelReleases(Source $source): void
    {
        $releases = [
            [
                'tag' => 'v12.1.1',
                'date' => '2025-01-21',
                'body' => "## What's Changed\n\n* [12.x] Fix route caching with enum bindings by @taylorotwell\n* [12.x] Improved query builder performance by @driesvints\n* [12.x] Fixed validation message for array fields\n\n**Full Changelog**: https://github.com/laravel/framework/compare/v12.1.0...v12.1.1",
                'prerelease' => false,
            ],
            [
                'tag' => 'v12.1.0',
                'date' => '2025-01-19',
                'body' => "## What's Changed\n\n* [12.x] Added new `Str::apa()` method by @taylorotwell\n* [12.x] Improved Eloquent lazy loading detection\n* [12.x] Added support for SQLite JSON operators\n* [12.x] New Blade directive `@pushOnce`\n\n**Full Changelog**: https://github.com/laravel/framework/compare/v12.0.0...v12.1.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v12.0.0',
                'date' => '2025-01-14',
                'body' => "## Laravel 12\n\nLaravel 12 continues the improvements made in Laravel 11.x by introducing:\n\n* PHP 8.4 support\n* Improved type declarations throughout\n* New `once()` helper function\n* Streamlined application structure\n* Enhanced Folio and Volt integration\n\n### Breaking Changes\n\n* Minimum PHP version is now 8.3\n* Removed deprecated methods from v11\n\n**Full Changelog**: https://github.com/laravel/framework/compare/v11.x...v12.0.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v11.42.0',
                'date' => '2025-01-07',
                'body' => "## What's Changed\n\n* [11.x] Backported security fixes\n* [11.x] Minor performance improvements\n* [11.x] Updated dependencies\n\n**Full Changelog**: https://github.com/laravel/framework/compare/v11.41.0...v11.42.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v11.41.0',
                'date' => '2024-12-28',
                'body' => "## What's Changed\n\n* [11.x] Added `whereJsonContainsKey` method\n* [11.x] Improved rate limiter middleware\n* [11.x] Bug fixes and improvements\n\n**Full Changelog**: https://github.com/laravel/framework/compare/v11.40.0...v11.41.0",
                'prerelease' => false,
            ],
        ];

        foreach ($releases as $release) {
            SourceItem::create([
                'source_id' => $source->id,
                'external_id' => 'laravel-'.str_replace('.', '-', $release['tag']),
                'title' => $release['tag'],
                'url' => "https://github.com/laravel/framework/releases/tag/{$release['tag']}",
                'published_at' => $release['date'],
                'raw_content' => $release['body'],
                'metadata' => [
                    'tag_name' => $release['tag'],
                    'prerelease' => $release['prerelease'],
                    'draft' => false,
                ],
            ]);
        }
    }

    private function createOpencodeReleases(Source $source): void
    {
        $releases = [
            [
                'tag' => 'v0.5.0',
                'date' => '2025-01-20',
                'body' => "## What's New\n\n* Added support for custom MCP servers\n* Improved context management\n* New slash commands system\n* Better error handling and recovery\n* Performance improvements for large codebases\n\n**Full Changelog**: https://github.com/anomalyco/opencode/compare/v0.4.0...v0.5.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v0.4.0',
                'date' => '2025-01-15',
                'body' => "## What's New\n\n* Multi-model support (Claude, GPT-4, etc.)\n* Improved file editing capabilities\n* Added project-level configuration\n* Better git integration\n\n**Full Changelog**: https://github.com/anomalyco/opencode/compare/v0.3.0...v0.4.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v0.3.0',
                'date' => '2025-01-08',
                'body' => "## What's New\n\n* Initial public release\n* Basic file operations (read, write, edit)\n* Code search with grep and glob\n* Bash command execution\n* Task delegation to sub-agents\n\n**Full Changelog**: https://github.com/anomalyco/opencode/releases/tag/v0.3.0",
                'prerelease' => false,
            ],
            [
                'tag' => 'v0.3.0-beta.2',
                'date' => '2025-01-05',
                'body' => "## Beta Release\n\n* Bug fixes from beta.1\n* Improved stability\n* Documentation updates\n\n**Full Changelog**: https://github.com/anomalyco/opencode/compare/v0.3.0-beta.1...v0.3.0-beta.2",
                'prerelease' => true,
            ],
            [
                'tag' => 'v0.3.0-beta.1',
                'date' => '2025-01-02',
                'body' => "## First Beta\n\n* Initial beta release for testing\n* Core functionality implemented\n* Known issues documented in README\n\n**Full Changelog**: https://github.com/anomalyco/opencode/releases/tag/v0.3.0-beta.1",
                'prerelease' => true,
            ],
        ];

        foreach ($releases as $release) {
            SourceItem::create([
                'source_id' => $source->id,
                'external_id' => 'opencode-'.str_replace('.', '-', $release['tag']),
                'title' => $release['tag'],
                'url' => "https://github.com/anomalyco/opencode/releases/tag/{$release['tag']}",
                'published_at' => $release['date'],
                'raw_content' => $release['body'],
                'metadata' => [
                    'tag_name' => $release['tag'],
                    'prerelease' => $release['prerelease'],
                    'draft' => false,
                ],
            ]);
        }
    }

    private function createDigestRuns(Digest $digest, Source ...$sources): void
    {
        // Completed run from yesterday
        $completedRun = DigestRun::create([
            'digest_id' => $digest->id,
            'period_start_at' => now()->subDays(2)->startOfDay(),
            'period_end_at' => now()->subDay()->startOfDay(),
            'status' => 'completed',
            'started_at' => now()->subDay()->setTime(9, 0, 0),
            'finished_at' => now()->subDay()->setTime(9, 2, 30),
            'ai_summary' => $this->generateAiSummary($sources),
        ]);

        // Attach some source items to the completed run
        $sourceItems = SourceItem::whereIn('source_id', collect($sources)->pluck('id'))
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        foreach ($sourceItems as $index => $item) {
            $completedRun->sourceItems()->attach($item->id, ['position' => $index + 1]);
        }

        // Pending run for today
        DigestRun::create([
            'digest_id' => $digest->id,
            'period_start_at' => now()->subDay()->startOfDay(),
            'period_end_at' => now()->startOfDay(),
            'status' => 'pending',
        ]);

        // Failed run from 3 days ago
        DigestRun::create([
            'digest_id' => $digest->id,
            'period_start_at' => now()->subDays(4)->startOfDay(),
            'period_end_at' => now()->subDays(3)->startOfDay(),
            'status' => 'failed',
            'started_at' => now()->subDays(3)->setTime(9, 0, 0),
            'finished_at' => now()->subDays(3)->setTime(9, 0, 45),
            'error' => 'GitHub API rate limit exceeded. Please try again later.',
        ]);
    }

    /**
     * @param  Source[]  $sources
     */
    private function generateAiSummary(array $sources): string
    {
        $summaries = [];

        foreach ($sources as $source) {
            $items = $source->items()->orderByDesc('published_at')->take(2)->get();
            $versions = $items->pluck('title')->implode(' and ');
            $summaries[] = "**{$source->name}** released {$versions} with various improvements and bug fixes.";
        }

        return "Here's a summary of your updates:\n\n".implode("\n\n", $summaries);
    }
}
