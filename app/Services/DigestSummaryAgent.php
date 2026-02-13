<?php

namespace App\Services;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class DigestSummaryAgent implements Agent
{
    use Promptable;

    public function instructions(): string
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
