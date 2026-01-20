<?php

namespace App\Actions;

use App\Enums\SourceType;
use App\Models\Source;
use App\Models\SourceItem;
use App\Services\GitHubService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FetchGitHubRepoItems
{
    public function __construct(
        private readonly GitHubService $github,
    ) {}

    /**
     * Fetch releases for a GitHub repository source and store them as SourceItems.
     *
     * @return Collection<int, SourceItem> Newly created items
     */
    public function __invoke(Source $source): Collection
    {
        if ($source->type !== SourceType::GitHubRepo->value) {
            throw new \InvalidArgumentException("Source is not a GitHub repository: {$source->id}");
        }

        $owner = $source->config['owner'];
        $repo = $source->config['repo'];

        try {
            $releases = $this->fetchReleases($owner, $repo, $source);
            $newItems = $this->storeReleases($source, $releases);

            $source->update([
                'last_fetched_at' => now(),
                'last_error' => null,
                'fetch_state' => $this->buildFetchState($releases, $source->fetch_state),
            ]);

            return $newItems;
        } catch (\Throwable $e) {
            $source->update([
                'last_fetched_at' => now(),
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch releases from GitHub API.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchReleases(string $owner, string $repo, Source $source): array
    {
        $releases = $this->github->fetchReleases($owner, $repo);
        $fetchState = $source->fetch_state ?? [];

        // Filter out releases we've already processed based on the last known release ID
        $lastProcessedId = $fetchState['last_release_id'] ?? null;

        if ($lastProcessedId !== null) {
            $releases = collect($releases)
                ->takeUntil(fn (array $release) => $release['id'] === $lastProcessedId)
                ->all();
        }

        // On first fetch, only include releases published after the source was created
        if ($source->last_fetched_at === null) {
            $releases = collect($releases)
                ->filter(fn (array $release) => $this->parseDate($release['published_at'])?->gte($source->created_at) ?? false)
                ->values()
                ->all();
        }

        return $releases;
    }

    /**
     * Store releases as SourceItems.
     *
     * @param  array<int, array<string, mixed>>  $releases
     * @return Collection<int, SourceItem>
     */
    private function storeReleases(Source $source, array $releases): Collection
    {
        $newItems = collect();

        foreach ($releases as $release) {
            $item = SourceItem::firstOrCreate(
                [
                    'source_id' => $source->id,
                    'external_id' => (string) $release['id'],
                ],
                [
                    'title' => $release['name'] ?: $release['tag_name'],
                    'url' => $release['html_url'],
                    'published_at' => $this->parseDate($release['published_at']),
                    'raw_content' => $release['body'] ?? '',
                    'metadata' => [
                        'tag_name' => $release['tag_name'],
                        'prerelease' => $release['prerelease'],
                        'draft' => $release['draft'],
                        'author' => $release['author']['login'] ?? null,
                        'tarball_url' => $release['tarball_url'] ?? null,
                        'zipball_url' => $release['zipball_url'] ?? null,
                    ],
                ]
            );

            if ($item->wasRecentlyCreated) {
                $newItems->push($item);
            }
        }

        return $newItems;
    }

    /**
     * Build the fetch state for incremental fetching.
     *
     * @param  array<int, array<string, mixed>>  $releases
     * @param  array<string, mixed>|null  $previousState
     * @return array<string, mixed>
     */
    private function buildFetchState(array $releases, ?array $previousState): array
    {
        if (empty($releases)) {
            return $previousState ?? [];
        }

        // The first release in the array is the newest
        $newestRelease = $releases[0];

        return [
            'last_release_id' => $newestRelease['id'],
            'last_release_tag' => $newestRelease['tag_name'],
            'last_fetch_count' => count($releases),
        ];
    }

    private function parseDate(?string $date): ?Carbon
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date);
    }
}
