<?php

use App\Models\Digest;
use App\Models\DigestRun;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

test('dashboard returns stats for the authenticated user', function () {
    $user = User::factory()->create();

    // Create 2 enabled digests and 1 disabled
    Digest::factory()->count(2)->for($user)->create(['is_enabled' => true]);
    Digest::factory()->for($user)->create(['is_enabled' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.activeDigests', 2)
        );
});

test('dashboard returns recent runs for user digests only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $digest = Digest::factory()->for($user)->create();
    $otherDigest = Digest::factory()->for($otherUser)->create();

    // Create runs for both users
    DigestRun::factory()->for($digest)->create(['status' => 'completed']);
    DigestRun::factory()->for($otherDigest)->create(['status' => 'completed']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('recentRuns', 1)
            ->where('recentRuns.0.digest_id', $digest->id)
        );
});

test('dashboard returns upcoming digests ordered by send_time', function () {
    $user = User::factory()->create();

    $laterDigest = Digest::factory()->for($user)->create([
        'is_enabled' => true,
        'send_time' => '18:00:00',
        'name' => 'Evening Digest',
    ]);

    $earlierDigest = Digest::factory()->for($user)->create([
        'is_enabled' => true,
        'send_time' => '09:00:00',
        'name' => 'Morning Digest',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->has('upcomingDigests', 2)
            ->where('upcomingDigests.0.name', 'Morning Digest')
            ->where('upcomingDigests.1.name', 'Evening Digest')
        );
});
