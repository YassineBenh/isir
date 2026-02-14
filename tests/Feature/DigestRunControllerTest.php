<?php

use App\Models\Destination;
use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('show', function () {
    it('displays digest run page', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $run = DigestRun::factory()->completed()->create([
            'digest_id' => $digest->id,
            'ai_summary' => 'Test AI summary content',
        ]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/runs/{$run->id}");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('digests/runs/show')
            ->has('digest')
            ->has('run')
            ->where('digest.id', $digest->id)
            ->where('run.id', $run->id)
        );
    });

    it('loads delivery attempts with destinations', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $run = DigestRun::factory()->completed()->create(['digest_id' => $digest->id]);

        $run->deliveryAttempts()->create([
            'destination_id' => $destination->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/runs/{$run->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('run.delivery_attempts', 1)
            ->has('run.delivery_attempts.0.destination')
        );
    });

    it('includes empty ai_summary when no summary is available', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $run = DigestRun::factory()->completed()->create([
            'digest_id' => $digest->id,
            'ai_summary' => null,
        ]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/runs/{$run->id}");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('run.id', $run->id)
            ->where('run.ai_summary', null)
        );
    });

    it('prevents viewing run from another users digest', function () {
        $otherUser = User::factory()->create();
        $otherDigest = Digest::factory()->create(['user_id' => $otherUser->id]);
        $run = DigestRun::factory()->create(['digest_id' => $otherDigest->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$otherDigest->id}/runs/{$run->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $digest = Digest::factory()->create();
        $run = DigestRun::factory()->create(['digest_id' => $digest->id]);

        $this->get("/digests/{$digest->id}/runs/{$run->id}")->assertRedirect('/login');
    });

    it('returns 404 for non-existent run', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/runs/99999");

        $response->assertNotFound();
    });
});
