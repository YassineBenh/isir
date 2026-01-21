<?php

use App\Jobs\FetchGitHubRepoItemsJob;
use App\Models\Source;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

it('dispatches at most 100 sources per run, prioritizing oldest fetch time', function () {
    Queue::fake();

    $recentSources = Source::factory()->count(100)->create([
        'last_fetched_at' => now()->subDay(),
    ]);

    $staleSources = Source::factory()->count(50)->create([
        'last_fetched_at' => now()->subDays(10),
    ]);

    Artisan::call('sources:fetch --type=github_repo');

    Queue::assertPushed(FetchGitHubRepoItemsJob::class, 100);

    $staleSources->each(function (Source $source) {
        Queue::assertPushed(FetchGitHubRepoItemsJob::class, function (FetchGitHubRepoItemsJob $job) use ($source) {
            return $job->source->is($source);
        });
    });
});
