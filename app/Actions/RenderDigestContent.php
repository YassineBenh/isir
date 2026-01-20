<?php

namespace App\Actions;

use App\Models\DigestRun;
use App\Models\SourceItem;
use Illuminate\Support\Collection;

class RenderDigestContent
{
    /**
     * Render the digest content as markdown.
     *
     * @param  Collection<int, SourceItem>  $items
     */
    public function __invoke(DigestRun $digestRun, Collection $items): string
    {
        $digest = $digestRun->digest;
        $periodStart = $digestRun->period_start_at->format('M j, Y');
        $periodEnd = $digestRun->period_end_at->format('M j, Y');

        $lines = [];
        $lines[] = "# {$digest->name}";
        $lines[] = '';
        $lines[] = "**Period:** {$periodStart} - {$periodEnd}";
        $lines[] = '';

        if ($items->isEmpty()) {
            $lines[] = '---';
            $lines[] = '';
            $lines[] = 'No updates during this period.';

            return implode("\n", $lines);
        }

        $lines[] = "**{$items->count()} update(s)**";
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        // Group items by source
        $itemsBySource = $items->groupBy(fn (SourceItem $item) => $item->source_id);

        foreach ($itemsBySource as $sourceId => $sourceItems) {
            $source = $sourceItems->first()->source;
            $lines[] = "## {$source->name}";
            $lines[] = '';

            foreach ($sourceItems as $item) {
                $lines[] = $this->renderItem($item);
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }

    private function renderItem(SourceItem $item): string
    {
        $lines = [];

        $tagName = $item->metadata['tag_name'] ?? null;
        $title = $tagName ? "**{$item->title}** (`{$tagName}`)" : "**{$item->title}**";

        $lines[] = "### {$title}";
        $lines[] = '';

        if ($item->published_at) {
            $lines[] = "*Released: {$item->published_at->format('M j, Y')}*";
            $lines[] = '';
        }

        if ($item->url) {
            $lines[] = "[View Release]({$item->url})";
            $lines[] = '';
        }

        if ($item->raw_content) {
            // Truncate long release notes
            $content = $item->raw_content;
            if (strlen($content) > 1000) {
                $content = substr($content, 0, 1000).'...';
            }
            $lines[] = $content;
        }

        return implode("\n", $lines);
    }
}
