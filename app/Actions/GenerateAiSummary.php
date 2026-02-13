<?php

namespace App\Actions;

use App\Models\DigestRun;
use App\Models\SourceItem;
use App\Services\AIService;
use Illuminate\Support\Collection;

class GenerateAiSummary
{
    public function __construct(
        private readonly AIService $aiService,
    ) {}

    /**
     * Generate an AI summary for digest source items.
     *
     * @param  Collection<int, SourceItem>  $items
     */
    public function __invoke(DigestRun $digestRun, Collection $items): string
    {
        if ($items->isEmpty()) {
            return 'No updates during this period.';
        }

        $renderedContent = $this->buildPromptContent($items);

        return $this->aiService->summarizeDigest($renderedContent);
    }

    /**
     * Build the prompt content from source items, grouped by source.
     *
     * @param  Collection<int, SourceItem>  $items
     */
    private function buildPromptContent(Collection $items): string
    {
        $lines = [];
        $lines[] = 'Summarize the following software updates:';
        $lines[] = '';

        $itemsBySource = $items->groupBy(fn (SourceItem $item) => $item->source_id);

        foreach ($itemsBySource as $sourceItems) {
            $source = $sourceItems->first()->source;
            $lines[] = "## Source: {$source->name}";
            $lines[] = '';

            foreach ($sourceItems as $item) {
                $lines[] = "### {$item->title}";

                if ($tagName = $item->metadata['tag_name'] ?? null) {
                    $lines[] = "Tag: {$tagName}";
                }

                if ($item->published_at) {
                    $lines[] = "Released: {$item->published_at->format('M j, Y')}";
                }

                if ($item->raw_content) {
                    $content = $item->raw_content;
                    if (strlen($content) > 2000) {
                        $content = substr($content, 0, 2000).'...';
                    }
                    $lines[] = '';
                    $lines[] = $content;
                }

                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
