<?php

namespace App\Jobs;

use App\Actions\FetchGitHubRepoItems;
use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchGitHubRepoItemsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Source $source,
    ) {}

    public function handle(FetchGitHubRepoItems $fetchGitHubRepoItems): void
    {
        if ($this->source->type !== SourceType::GitHubRepo->value) {
            Log::warning("FetchGitHubRepoItemsJob: Source {$this->source->id} is not a GitHub repository");

            return;
        }

        if (! $this->source->is_enabled) {
            Log::info("FetchGitHubRepoItemsJob: Source {$this->source->id} is disabled, skipping");

            return;
        }

        $newItems = $fetchGitHubRepoItems($this->source);

        Log::info("FetchGitHubRepoItemsJob: Fetched {$newItems->count()} new items for source {$this->source->id}");
    }
}
