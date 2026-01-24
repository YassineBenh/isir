<?php

namespace App\Actions;

use App\Enums\DigestRunStatus;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\SourceItem;
use Illuminate\Support\Collection;

class ProcessDigestRun
{
    public function __construct(
        private readonly GenerateAiSummary $generateAiSummary,
        private readonly DeliverDigestRun $deliverDigestRun,
    ) {}

    /**
     * Process a digest run: gather items, generate AI summary, and deliver.
     */
    public function __invoke(Digest $digest): DigestRun
    {
        $periodStart = $digest->last_successful_run_at ?? $digest->created_at;
        $periodEnd = now();

        $digestRun = $digest->runs()->create([
            'period_start_at' => $periodStart,
            'period_end_at' => $periodEnd,
            'status' => DigestRunStatus::Running->value,
            'started_at' => now(),
        ]);

        try {
            $items = $this->gatherItems($digest, $periodStart, $periodEnd);

            $this->attachItems($digestRun, $items);

            if ($digest->ai_enabled) {
                $aiSummary = ($this->generateAiSummary)($digestRun, $items);

                $digestRun->update([
                    'ai_summary' => $aiSummary,
                ]);
            }

            ($this->deliverDigestRun)($digestRun);

            $digestRun->update([
                'status' => DigestRunStatus::Completed->value,
                'finished_at' => now(),
            ]);

            $digest->update([
                'last_successful_run_at' => $periodEnd,
            ]);

            return $digestRun;
        } catch (\Throwable $e) {
            $digestRun->update([
                'status' => DigestRunStatus::Failed->value,
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Gather source items from the digest's sources within the period.
     *
     * @return Collection<int, SourceItem>
     */
    private function gatherItems(Digest $digest, mixed $periodStart, mixed $periodEnd): Collection
    {
        $sourceIds = $digest->sources()->pluck('sources.id');

        return SourceItem::query()
            ->whereIn('source_id', $sourceIds)
            ->where('published_at', '>=', $periodStart)
            ->where('published_at', '<=', $periodEnd)
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Attach items to the digest run with position.
     *
     * @param  Collection<int, SourceItem>  $items
     */
    private function attachItems(DigestRun $digestRun, Collection $items): void
    {
        $attachData = [];
        $position = 1;

        foreach ($items as $item) {
            $attachData[$item->id] = ['position' => $position++];
        }

        $digestRun->sourceItems()->attach($attachData);
    }
}
