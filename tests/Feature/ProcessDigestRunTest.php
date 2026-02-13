<?php

use App\Actions\ProcessDigestRun;
use App\Enums\DeliveryAttemptStatus;
use App\Enums\DigestRunStatus;
use App\Models\DeliveryAttempt;
use App\Models\Destination;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\Source;
use App\Models\SourceItem;
use App\Models\User;
use App\Services\DigestSummaryAgent;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Configure AI for tests
    config(['ai.default' => 'anthropic']);
    config(['ai.providers.anthropic.key' => 'test-api-key']);
    config(['ai.model' => 'claude-sonnet-4-20250514']);

    $this->user = User::factory()->create();
    $this->digest = Digest::factory()->create([
        'user_id' => $this->user->id,
        'last_successful_run_at' => null,
        'ai_enabled' => true,
    ]);
    $this->source = Source::factory()->create();
    $this->digest->sources()->attach($this->source);

    // Set up default AI fake
    DigestSummaryAgent::fake([
        'AI generated summary for the digest.',
    ]);
});

describe('ProcessDigestRun', function () {
    it('creates a digest run with correct period', function () {
        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun)->toBeInstanceOf(DigestRun::class);
        expect($digestRun->digest_id)->toBe($this->digest->id);
        expect($digestRun->period_start_at->toDateTimeString())
            ->toBe($this->digest->created_at->toDateTimeString());
        expect($digestRun->status)->toBe(DigestRunStatus::Completed->value);
    });

    it('uses last_successful_run_at as period start when available', function () {
        $lastRun = now()->subDay();
        $this->digest->update(['last_successful_run_at' => $lastRun]);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun->period_start_at->toDateTimeString())
            ->toBe($lastRun->toDateTimeString());
    });

    it('gathers source items from the period', function () {
        // Set last_successful_run_at to 2 days ago (the period start)
        $this->digest->update(['last_successful_run_at' => now()->subDays(2)]);

        // Create items within the period (after last run)
        $withinPeriod = SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subHour(),
        ]);

        // Create item outside the period (before last run)
        $beforePeriod = SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subWeek(),
        ]);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun->sourceItems)->toHaveCount(1);
        expect($digestRun->sourceItems->first()->id)->toBe($withinPeriod->id);
    });

    it('generates AI summary when ai_enabled is true', function () {
        $this->digest->update(['last_successful_run_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'title' => 'v1.0.0',
            'published_at' => now()->subHour(),
        ]);

        DigestSummaryAgent::fake([
            '**Laravel Framework** released v1.0.0 with new features.',
        ]);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun->ai_summary)->toBe('**Laravel Framework** released v1.0.0 with new features.');
    });

    it('does not generate AI summary when ai_enabled is false', function () {
        $this->digest->update([
            'ai_enabled' => false,
            'last_successful_run_at' => now()->subDay(),
        ]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'title' => 'v1.0.0',
            'published_at' => now()->subHour(),
        ]);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun->ai_summary)->toBeNull();
        DigestSummaryAgent::assertNeverPrompted();
    });

    it('generates no updates message when no items', function () {
        DigestSummaryAgent::fake([
            'No updates during this period.',
        ]);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect($digestRun->ai_summary)->toBe('No updates during this period.');
        DigestSummaryAgent::assertNeverPrompted();
    });

    it('updates last_successful_run_at on success', function () {
        $action = app(ProcessDigestRun::class);
        $action($this->digest);

        $this->digest->refresh();
        expect($this->digest->last_successful_run_at)->not->toBeNull();
    });

    it('delivers to enabled destinations', function () {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $this->digest->update(['last_successful_run_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subHour(),
        ]);

        $destination = Destination::factory()->slack()->create([
            'user_id' => $this->user->id,
        ]);
        $this->digest->destinations()->attach($destination);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        expect(DeliveryAttempt::count())->toBe(1);
        $attempt = DeliveryAttempt::first();
        expect($attempt->digest_run_id)->toBe($digestRun->id);
        expect($attempt->destination_id)->toBe($destination->id);
        expect($attempt->status)->toBe(DeliveryAttemptStatus::Sent->value);
    });

    it('skips disabled destinations', function () {
        $this->digest->update(['last_successful_run_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subHour(),
        ]);

        $destination = Destination::factory()->slack()->disabled()->create([
            'user_id' => $this->user->id,
        ]);
        $this->digest->destinations()->attach($destination);

        $action = app(ProcessDigestRun::class);
        $action($this->digest);

        expect(DeliveryAttempt::count())->toBe(0);
    });

    it('does not deliver when no items are gathered', function () {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $destination = Destination::factory()->slack()->create([
            'user_id' => $this->user->id,
        ]);
        $this->digest->destinations()->attach($destination);

        $action = app(ProcessDigestRun::class);
        $action($this->digest);

        expect(DeliveryAttempt::count())->toBe(0);
        Http::assertNothingSent();
    });

    it('records failed delivery attempts', function () {
        Http::fake([
            'hooks.slack.com/*' => Http::response('error', 500),
        ]);

        $this->digest->update(['last_successful_run_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subHour(),
        ]);

        $destination = Destination::factory()->slack()->create([
            'user_id' => $this->user->id,
        ]);
        $this->digest->destinations()->attach($destination);

        $action = app(ProcessDigestRun::class);
        $digestRun = $action($this->digest);

        $attempt = DeliveryAttempt::first();
        expect($attempt->status)->toBe(DeliveryAttemptStatus::Failed->value);
        expect($attempt->error)->toContain('500');

        // Digest run should still complete even if delivery fails
        expect($digestRun->status)->toBe(DigestRunStatus::Completed->value);
    });

    it('delivers to multiple destinations', function () {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
            'discord.com/*' => Http::response(['id' => 'msg123'], 200),
        ]);

        $this->digest->update(['last_successful_run_at' => now()->subDay()]);

        SourceItem::factory()->create([
            'source_id' => $this->source->id,
            'published_at' => now()->subHour(),
        ]);

        $slack = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $discord = Destination::factory()->discord()->create(['user_id' => $this->user->id]);

        $this->digest->destinations()->attach([$slack->id, $discord->id]);

        $action = app(ProcessDigestRun::class);
        $action($this->digest);

        expect(DeliveryAttempt::count())->toBe(2);
        expect(DeliveryAttempt::where('status', DeliveryAttemptStatus::Sent->value)->count())->toBe(2);
    });
});
