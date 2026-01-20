<?php

use App\Jobs\ProcessDigestRunJob;
use App\Models\Digest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

afterEach(function () {
    Carbon::setTestNow();
});

it('dispatches daily digests based on local day changes', function () {
    Queue::fake();

    Carbon::setTestNow(Carbon::create(2026, 1, 21, 14, 2, 0, 'UTC'));

    $digest = Digest::factory()->daily()->create([
        'timezone' => 'America/New_York',
        'send_time' => '09:00',
        'last_successful_run_at' => Carbon::create(2026, 1, 20, 14, 30, 0, 'UTC'),
        'last_dispatched_at' => null,
    ]);

    Artisan::call('digests:dispatch-due');

    Queue::assertPushed(ProcessDigestRunJob::class, function (ProcessDigestRunJob $job) use ($digest) {
        return $job->digest->is($digest);
    });

    expect($digest->refresh()->last_dispatched_at)->not->toBeNull();
});

it('does not dispatch before the send time window', function () {
    Queue::fake();

    Carbon::setTestNow(Carbon::create(2026, 1, 21, 8, 58, 0, 'UTC'));

    Digest::factory()->daily()->create([
        'timezone' => 'UTC',
        'send_time' => '09:00',
    ]);

    Artisan::call('digests:dispatch-due');

    Queue::assertNothingPushed();
});

it('skips dispatch when already dispatched today', function () {
    Queue::fake();

    Carbon::setTestNow(Carbon::create(2026, 1, 21, 9, 2, 0, 'UTC'));

    Digest::factory()->daily()->create([
        'timezone' => 'UTC',
        'send_time' => '09:00',
        'last_successful_run_at' => Carbon::create(2026, 1, 20, 9, 0, 0, 'UTC'),
        'last_dispatched_at' => Carbon::create(2026, 1, 21, 9, 0, 0, 'UTC'),
    ]);

    Artisan::call('digests:dispatch-due');

    Queue::assertNothingPushed();
});

it('dispatches weekly digests at the week boundary', function () {
    Queue::fake();

    Carbon::setTestNow(Carbon::create(2026, 1, 18, 9, 2, 0, 'UTC'));

    $digest = Digest::factory()->weekly()->create([
        'timezone' => 'UTC',
        'send_time' => '09:00',
        'send_day_of_week' => 0,
        'last_successful_run_at' => Carbon::create(2026, 1, 17, 9, 0, 0, 'UTC'),
        'last_dispatched_at' => null,
    ]);

    Artisan::call('digests:dispatch-due');

    Queue::assertPushed(ProcessDigestRunJob::class, function (ProcessDigestRunJob $job) use ($digest) {
        return $job->digest->is($digest);
    });
});
