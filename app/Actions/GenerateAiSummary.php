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

        $response = $this->aiService->text()
            ->withSystemPrompt($this->getSystemPrompt())
            ->withPrompt($renderedContent)
            ->asText();

        return $response->text;
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

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a helpful assistant that summarizes software release updates for a digest email.

Your task:
- Provide a brief, human-friendly summary of the updates
- Treat each source (project/repository) separately
- For each source, write 2-3 sentences highlighting the most important changes
- Use markdown formatting with **bold** for source names
- Keep the overall summary concise and scannable
- Focus on what matters to developers: new features, breaking changes, important fixes

Format your response as:
**Source Name**
Brief summary of the key updates.

**Another Source**
Brief summary of the key updates.
PROMPT;
    }
}
