<?php

namespace App\Console\Commands;

use App\Enums\SourceType;
use App\Jobs\FetchGitHubRepoItemsJob;
use App\Models\Source;
use Illuminate\Console\Command;

class FetchSourcesCommand extends Command
{
    private const BATCH_SIZE = 100;

    protected $signature = 'sources:fetch {--type= : The source type to fetch (github_repo, rss_feed, youtube_channel)}';

    protected $description = 'Dispatch jobs to fetch items from sources';

    public function handle(): int
    {
        $type = $this->option('type');

        if ($type && ! SourceType::tryFrom($type)) {
            $this->error("Invalid source type: {$type}");
            $this->info('Valid types: '.implode(', ', array_column(SourceType::cases(), 'value')));

            return self::FAILURE;
        }

        $dispatched = $this->dispatchJobs($type);

        $this->info("Dispatched {$dispatched} fetch job(s).");

        return self::SUCCESS;
    }

    private function dispatchJobs(?string $type): int
    {
        $query = Source::query()->where('is_enabled', true);

        if ($type) {
            $query->where('type', $type);
        }

        $sources = $query
            ->orderByRaw('last_fetched_at is null desc')
            ->orderBy('last_fetched_at')
            ->limit(self::BATCH_SIZE)
            ->get();

        $dispatched = 0;

        $sources->each(function (Source $source) use (&$dispatched) {
            $this->dispatchForSource($source);
            $dispatched++;
        });

        return $dispatched;
    }

    private function dispatchForSource(Source $source): void
    {
        match ($source->type) {
            SourceType::GitHubRepo->value => FetchGitHubRepoItemsJob::dispatch($source),
            // Future: Add other source types here
            // SourceType::RssFeed->value => FetchRssFeedItemsJob::dispatch($source),
            // SourceType::YouTubeChannel->value => FetchYouTubeChannelItemsJob::dispatch($source),
            default => $this->warn("No job handler for source type: {$source->type}"),
        };
    }
}
