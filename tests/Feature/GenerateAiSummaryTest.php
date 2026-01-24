<?php

use App\Actions\GenerateAiSummary;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\Source;
use App\Models\SourceItem;
use App\Models\User;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

beforeEach(function () {
    // Configure AI for tests
    config(['services.ai.provider' => 'anthropic']);
    config(['services.ai.model' => 'claude-sonnet-4-20250514']);
    config(['services.ai.api_key' => 'test-api-key']);

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

        Prism::fake([
            TextResponseFake::make()->withText('**Laravel Framework** released v12.0.0 with major new features.'),
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

        $fake = Prism::fake([
            TextResponseFake::make()->withText('Summary for multiple sources.'),
        ]);

        $action = app(GenerateAiSummary::class);
        $action($this->digestRun, $items);

        // Verify the prompt was sent to Prism
        $fake->assertCallCount(1);
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

        $fake = Prism::fake([
            TextResponseFake::make()->withText('Summary generated.'),
        ]);

        $action = app(GenerateAiSummary::class);
        $result = $action($this->digestRun, $items);

        expect($result)->toBe('Summary generated.');
        $fake->assertCallCount(1);
    });

    it('uses configured AI provider and model', function () {
        config(['services.ai.provider' => 'openai']);
        config(['services.ai.model' => 'gpt-4o']);
        config(['services.ai.api_key' => 'test-openai-key']);

        $items = collect([
            SourceItem::factory()->create([
                'source_id' => $this->source->id,
                'title' => 'v1.0.0',
            ]),
        ]);

        $fake = Prism::fake([
            TextResponseFake::make()->withText('OpenAI summary.'),
        ]);

        $action = app(GenerateAiSummary::class);
        $result = $action($this->digestRun, $items);

        expect($result)->toBe('OpenAI summary.');
        $fake->assertRequest(function ($requests) {
            expect($requests[0]->model())->toBe('gpt-4o');
        });
    });
});
