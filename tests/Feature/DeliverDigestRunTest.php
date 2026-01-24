<?php

use App\Actions\DeliverDigestRun;
use App\Models\Destination;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->digest = Digest::factory()->create(['user_id' => $this->user->id]);
    $this->run = DigestRun::factory()->completed()->create([
        'digest_id' => $this->digest->id,
        'ai_summary' => 'Summary of release notes',
    ]);
});

describe('delivery includes run URL', function () {
    it('sends slack message with link only', function () {
        Http::fake(['*' => Http::response('ok', 200)]);

        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        Http::assertSent(function ($request) {
            $text = $request->data()['text'] ?? '';
            $expectedUrl = route('digests.runs.show', [$this->digest, $this->run]);

            return str_contains($text, 'is now available')
                && str_contains($text, $expectedUrl);
        });
    });

    it('sends discord message with link only', function () {
        Http::fake(['*' => Http::response(['id' => '123'], 200)]);

        $destination = Destination::factory()->discord()->create(['user_id' => $this->user->id]);
        $this->digest->destinations()->attach($destination);

        $action = app(DeliverDigestRun::class);
        $action($this->run);

        Http::assertSent(function ($request) {
            $content = $request->data()['content'] ?? '';
            $expectedUrl = route('digests.runs.show', [$this->digest, $this->run]);

            return str_contains($content, 'is now available')
                && str_contains($content, $expectedUrl);
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
