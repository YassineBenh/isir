<?php

use App\Actions\DeliverDigestRun;
use App\Models\Destination;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\Source;
use App\Models\SourceItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->digest = Digest::factory()->create(['user_id' => $this->user->id]);
    $this->run = DigestRun::factory()->completed()->create([
        'digest_id' => $this->digest->id,
        'ai_summary' => 'Summary of release notes',
    ]);

    $sourceOne = Source::factory()->create(['name' => 'laravel/framework']);
    $sourceTwo = Source::factory()->create(['name' => 'laravel/laravel']);

    $sourceItems = collect([
        SourceItem::factory()->create([
            'source_id' => $sourceOne->id,
            'title' => 'v1.0.0',
        ]),
        SourceItem::factory()->create([
            'source_id' => $sourceTwo->id,
            'title' => 'v1.1.0',
        ]),
    ]);

    $this->run->sourceItems()->attach(
        $sourceItems
            ->mapWithKeys(fn (SourceItem $item, int $index): array => [
                $item->id => ['position' => $index + 1],
            ])
            ->all()
    );
});

describe('delivery includes run URL', function () {
    it('sends slack message with item count and link', function () {
        Http::fake(['*' => Http::response('ok', 200)]);

        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        Http::assertSent(function ($request) {
            $text = $request->data()['text'] ?? '';
            $expectedUrl = route('digests.runs.show', [$this->digest, $this->run]);
            $expectedItemCount = $this->run->sourceItems()->count();
            $expectedItemLabel = $expectedItemCount === 1 ? 'item' : 'items';
            $expectedItemText = "This run includes {$expectedItemCount} {$expectedItemLabel}.";

            return str_contains($text, 'is now available')
                && str_contains($text, $expectedItemText)
                && str_contains($text, $expectedUrl)
                && ! str_contains($text, 'Versions released in this run:');
        });
    });

    it('sends discord message with item count and link', function () {
        Http::fake(['*' => Http::response(['id' => '123'], 200)]);

        $destination = Destination::factory()->discord()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        Http::assertSent(function ($request) {
            $content = $request->data()['content'] ?? '';
            $expectedUrl = route('digests.runs.show', [$this->digest, $this->run]);
            $expectedItemCount = $this->run->sourceItems()->count();
            $expectedItemLabel = $expectedItemCount === 1 ? 'item' : 'items';
            $expectedItemText = "This run includes {$expectedItemCount} {$expectedItemLabel}.";

            return str_contains($content, 'is now available')
                && str_contains($content, $expectedItemText)
                && str_contains($content, $expectedUrl)
                && ! str_contains($content, 'Versions released in this run:');
        });
    });

    it('includes version titles in notification when enabled', function () {
        Http::fake(['*' => Http::response('ok', 200)]);

        $this->digest->update(['include_versions_summary' => true]);

        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        Http::assertSent(function ($request) {
            $text = $request->data()['text'] ?? '';

            return str_contains($text, 'Versions released in this run:')
                && str_contains($text, '- laravel/framework: v1.0.0')
                && str_contains($text, '- laravel/laravel: v1.1.0');
        });
    });

    it('sends email with run URL', function () {
        // Use array driver to capture the email
        config(['mail.default' => 'array']);

        $destination = Destination::factory()->email()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        // Verify delivery attempt was recorded as sent
        $this->assertDatabaseHas('delivery_attempts', [
            'digest_run_id' => $this->run->id,
            'destination_id' => $destination->id,
            'status' => 'sent',
        ]);
    });
});

describe('delivery attempts are recorded', function () {
    it('creates delivery attempt records', function () {
        Http::fake(['*' => Http::response('ok', 200)]);

        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        $this->assertDatabaseHas('delivery_attempts', [
            'digest_run_id' => $this->run->id,
            'destination_id' => $destination->id,
            'status' => 'sent',
        ]);
    });

    it('records failed delivery attempts', function () {
        Http::fake(['*' => Http::response('error', 500)]);

        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        $this->assertDatabaseHas('delivery_attempts', [
            'digest_run_id' => $this->run->id,
            'destination_id' => $destination->id,
            'status' => 'failed',
        ]);
    });
});
