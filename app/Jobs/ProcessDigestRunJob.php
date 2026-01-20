<?php

namespace App\Jobs;

use App\Actions\ProcessDigestRun;
use App\Models\Digest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDigestRunJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public Digest $digest,
    ) {}

    public function handle(ProcessDigestRun $processDigestRun): void
    {
        if (! $this->digest->is_enabled) {
            Log::info("ProcessDigestRunJob: Digest {$this->digest->id} is disabled, skipping");

            return;
        }

        $digestRun = $processDigestRun($this->digest);

        Log::info("ProcessDigestRunJob: Completed digest run {$digestRun->id} for digest {$this->digest->id}");
    }
}
