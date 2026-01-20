<?php

use App\Actions\FetchGitHubRepoItems;
use App\Enums\SourceType;
use App\Models\Source;
use App\Models\SourceItem;
use App\Services\GitHubService;

beforeEach(function () {
    $this->source = Source::factory()->create([
        'type' => SourceType::GitHubRepo->value,
        'config' => [
            'owner' => 'laravel',
            'repo' => 'framework',
        ],
    ]);
});

describe('FetchGitHubRepoItems', function () {
    it('fetches releases and stores them as source items', function () {
        // Mark as already fetched so we don't filter by created_at
        $this->source->update(['last_fetched_at' => now()->subDay()]);

        $releases = [
            [
                'id' => 12345,
                'name' => 'v10.0.0',
                'tag_name' => 'v10.0.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => now()->subHour()->toIso8601String(),
                'body' => 'Release notes for v10.0.0',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => 'https://example.com/tarball',
                'zipball_url' => 'https://example.com/zipball',
            ],
            [
                'id' => 12344,
                'name' => 'v9.52.0',
                'tag_name' => 'v9.52.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v9.52.0',
                'published_at' => now()->subHours(2)->toIso8601String(),
                'body' => 'Release notes for v9.52.0',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
        ];

        $this->mock(GitHubService::class, function ($mock) use ($releases) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn($releases);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        expect($newItems)->toHaveCount(2);
        expect(SourceItem::count())->toBe(2);

        $firstItem = SourceItem::where('external_id', '12345')->first();
        expect($firstItem)->not->toBeNull();
        expect($firstItem->title)->toBe('v10.0.0');
        expect($firstItem->url)->toBe('https://github.com/laravel/framework/releases/tag/v10.0.0');
        expect($firstItem->metadata['tag_name'])->toBe('v10.0.0');
        expect($firstItem->metadata['prerelease'])->toBeFalse();
        expect($firstItem->metadata['author'])->toBe('taylorotwell');

        $this->source->refresh();
        expect($this->source->last_fetched_at)->not->toBeNull();
        expect($this->source->last_error)->toBeNull();
        expect($this->source->fetch_state['last_release_id'])->toBe(12345);
    });

    it('skips already processed releases based on fetch state', function () {
        $this->source->update([
            'last_fetched_at' => now()->subDay(),
            'fetch_state' => ['last_release_id' => 12344],
        ]);

        $releases = [
            [
                'id' => 12345,
                'name' => 'v10.0.0',
                'tag_name' => 'v10.0.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => now()->subHour()->toIso8601String(),
                'body' => 'New release',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
            [
                'id' => 12344,
                'name' => 'v9.52.0',
                'tag_name' => 'v9.52.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v9.52.0',
                'published_at' => now()->subHours(2)->toIso8601String(),
                'body' => 'Already processed',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
        ];

        $this->mock(GitHubService::class, function ($mock) use ($releases) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn($releases);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        // Should only create the new release, not the one matching last_release_id
        expect($newItems)->toHaveCount(1);
        expect(SourceItem::count())->toBe(1);
        expect(SourceItem::first()->external_id)->toBe('12345');
    });

    it('does not create duplicate items', function () {
        $this->source->update(['last_fetched_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'external_id' => '12345',
        ]);

        $releases = [
            [
                'id' => 12345,
                'name' => 'v10.0.0',
                'tag_name' => 'v10.0.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => now()->subHour()->toIso8601String(),
                'body' => 'Release notes',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
        ];

        $this->mock(GitHubService::class, function ($mock) use ($releases) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn($releases);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        expect($newItems)->toHaveCount(0);
        expect(SourceItem::count())->toBe(1);
    });

    it('stores error on failure', function () {
        $this->mock(GitHubService::class, function ($mock) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andThrow(new \RuntimeException('API rate limit exceeded'));
        });

        $action = app(FetchGitHubRepoItems::class);

        expect(fn () => $action($this->source))->toThrow(\RuntimeException::class);

        $this->source->refresh();
        expect($this->source->last_fetched_at)->not->toBeNull();
        expect($this->source->last_error)->toBe('API rate limit exceeded');
    });

    it('throws exception for non-github sources', function () {
        $rssFeed = Source::factory()->rssFeed()->create();

        $this->mock(GitHubService::class);

        $action = app(FetchGitHubRepoItems::class);

        expect(fn () => $action($rssFeed))->toThrow(\InvalidArgumentException::class);
    });

    it('handles empty releases gracefully', function () {
        $this->source->update(['last_fetched_at' => now()->subDay()]);

        $this->mock(GitHubService::class, function ($mock) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn([]);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        expect($newItems)->toHaveCount(0);
        expect(SourceItem::count())->toBe(0);

        $this->source->refresh();
        expect($this->source->last_fetched_at)->not->toBeNull();
        expect($this->source->last_error)->toBeNull();
    });

    it('only fetches releases after source created_at on first fetch', function () {
        // Source has never been fetched (last_fetched_at is null)
        expect($this->source->last_fetched_at)->toBeNull();

        $releases = [
            [
                'id' => 12345,
                'name' => 'v10.0.0',
                'tag_name' => 'v10.0.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => now()->addHour()->toIso8601String(), // After source created
                'body' => 'New release after source creation',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
            [
                'id' => 12344,
                'name' => 'v9.52.0',
                'tag_name' => 'v9.52.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v9.52.0',
                'published_at' => now()->subWeek()->toIso8601String(), // Before source created
                'body' => 'Old release before source creation',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
            [
                'id' => 12343,
                'name' => 'v9.51.0',
                'tag_name' => 'v9.51.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v9.51.0',
                'published_at' => now()->subMonth()->toIso8601String(), // Way before source created
                'body' => 'Even older release',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
        ];

        $this->mock(GitHubService::class, function ($mock) use ($releases) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn($releases);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        // Should only include the release published after source was created
        expect($newItems)->toHaveCount(1);
        expect(SourceItem::count())->toBe(1);
        expect(SourceItem::first()->external_id)->toBe('12345');
        expect(SourceItem::first()->title)->toBe('v10.0.0');
    });

    it('fetches all releases on subsequent fetches regardless of created_at', function () {
        // Source has been fetched before
        $this->source->update(['last_fetched_at' => now()->subDay()]);

        $releases = [
            [
                'id' => 12345,
                'name' => 'v10.0.0',
                'tag_name' => 'v10.0.0',
                'html_url' => 'https://github.com/laravel/framework/releases/tag/v10.0.0',
                'published_at' => now()->subWeek()->toIso8601String(), // Before source created but should be included
                'body' => 'Release',
                'prerelease' => false,
                'draft' => false,
                'author' => ['login' => 'taylorotwell'],
                'tarball_url' => null,
                'zipball_url' => null,
            ],
        ];

        $this->mock(GitHubService::class, function ($mock) use ($releases) {
            $mock->shouldReceive('fetchReleases')
                ->with('laravel', 'framework')
                ->once()
                ->andReturn($releases);
        });

        $action = app(FetchGitHubRepoItems::class);
        $newItems = $action($this->source);

        // Should include all releases since it's not the first fetch
        expect($newItems)->toHaveCount(1);
        expect(SourceItem::count())->toBe(1);
    });
});
