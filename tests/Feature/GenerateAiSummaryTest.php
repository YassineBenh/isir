<?php

use App\Actions\GenerateAiSummary;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\Source;
use App\Models\SourceItem;
use App\Models\User;
use App\Services\DigestSummaryAgent;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    // Configure AI for tests
    config(['ai.default' => 'anthropic']);
    config(['ai.providers.anthropic.key' => 'test-api-key']);
    config(['ai.providers.anthropic.model' => 'claude-sonnet-4-20250514']);

    $this->user = User::factory()->create();
    $this->digest = Digest::factory()->create([
        'user_id' => $this->user->id,
        'ai_enabled' => true,
    ]);
    $this->source = Source::factory()->create(['name' => 'Laravel Framework']);
    $this->digest->sources()->attach($this->source);
    $this->digestRun = DigestRun::factory()->running()->create([
        'digest_id' => $this->digest->id,
    ]);
});

describe('GenerateAiSummary', function () {
    it('returns no updates message when items collection is empty', function () {
        $action = app(GenerateAiSummary::class);

        $result = $action($this->digestRun, collect());

        expect($result)->toBe('No updates during this period.');
    });

    it('generates AI summary for source items', function () {
        $items = collect([
            SourceItem::factory()->create([
                'source_id' => $this->source->id,
                'title' => 'v12.0.0',
                'raw_content' => 'Major release with new features.',
                'metadata' => ['tag_name' => 'v12.0.0'],
            ]),
        ]);

        DigestSummaryAgent::fake([
            '**Laravel Framework** released v12.0.0 with major new features.',
        ]);

        $action = app(GenerateAiSummary::class);
        $result = $action($this->digestRun, $items);

        expect($result)->toBe('**Laravel Framework** released v12.0.0 with major new features.');
    });

    it('groups items by source in the prompt', function () {
        $source2 = Source::factory()->create(['name' => 'Inertia.js']);

        $items = collect([
            SourceItem::factory()->create([
                'source_id' => $this->source->id,
                'title' => 'v12.0.0',
            ]),
            SourceItem::factory()->create([
                'source_id' => $source2->id,
                'title' => 'v2.0.0',
            ]),
        ]);

        DigestSummaryAgent::fake([
            'Summary for multiple sources.',
        ]);

        $action = app(GenerateAiSummary::class);
        $action($this->digestRun, $items);

        DigestSummaryAgent::assertPrompted(function (AgentPrompt $prompt) {
            return $prompt->contains('## Source: Laravel Framework')
                && $prompt->contains('## Source: Inertia.js');
        });
    });

    it('truncates long content in the prompt', function () {
        $longContent = str_repeat('a', 3000);

        $items = collect([
            SourceItem::factory()->create([
                'source_id' => $this->source->id,
                'title' => 'v1.0.0',
                'raw_content' => $longContent,
            ]),
        ]);

        DigestSummaryAgent::fake([
            'Summary generated.',
        ]);

        $action = app(GenerateAiSummary::class);
        $result = $action($this->digestRun, $items);

        expect($result)->toBe('Summary generated.');
        DigestSummaryAgent::assertPrompted(function (AgentPrompt $prompt) {
            return $prompt->contains(str_repeat('a', 2000).'...');
        });
    });

    it('uses configured AI provider and model', function () {
        config(['ai.default' => 'openai']);
        config(['ai.providers.openai.key' => 'test-openai-key']);
        config(['ai.providers.openai.model' => 'gpt-4o']);

        $items = collect([
            SourceItem::factory()->create([
                'source_id' => $this->source->id,
                'title' => 'v1.0.0',
            ]),
        ]);

        DigestSummaryAgent::fake([
            'OpenAI summary.',
        ]);

        $action = app(GenerateAiSummary::class);
        $result = $action($this->digestRun, $items);

        expect($result)->toBe('OpenAI summary.');
        DigestSummaryAgent::assertPrompted(function (AgentPrompt $prompt) {
            expect($prompt->provider()->name())->toBe('openai');
            expect($prompt->model)->toBe('gpt-4o');

            return true;
        });
    });
});
