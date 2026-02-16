<?php

use App\Models\Destination;
use App\Models\Digest;
use App\Models\Source;
use App\Models\User;
use App\Services\GitHubService;

beforeEach(function () {
    $this->user = User::factory()->create();

    // Mock GitHubService to always return that repos exist
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('repoExistsByUrl')
            ->andReturn(['exists' => true, 'error' => null]);
    });
});

describe('show', function () {
    it('displays digest show page with runs', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $source = Source::factory()->create();
        $destination = Destination::factory()->create(['user_id' => $this->user->id]);
        $digest->sources()->attach($source);
        $digest->destinations()->attach($destination);

        $runs = \App\Models\DigestRun::factory(3)->create(['digest_id' => $digest->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('digests/show')
            ->has('digest')
            ->has('runs.data', 3)
            ->where('digest.id', $digest->id)
        );
    });

    it('loads sources and destinations', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $sources = Source::factory(2)->create();
        $destinations = Destination::factory(2)->create(['user_id' => $this->user->id]);
        $digest->sources()->attach($sources);
        $digest->destinations()->attach($destinations);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('digest.sources', 2)
            ->has('digest.destinations', 2)
        );
    });

    it('prevents viewing another users digest', function () {
        $otherDigest = Digest::factory()->create();

        $response = $this->actingAs($this->user)->get("/digests/{$otherDigest->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $digest = Digest::factory()->create();

        $this->get("/digests/{$digest->id}")->assertRedirect('/login');
    });

    it('paginates runs', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        \App\Models\DigestRun::factory(15)->create(['digest_id' => $digest->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}");

        $response->assertInertia(fn ($page) => $page
            ->has('runs.data', 10)
            ->where('runs.total', 15)
            ->where('runs.last_page', 2)
        );
    });
});

describe('index', function () {
    it('displays digests index page', function () {
        $digests = Digest::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/digests');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('digests/index')
            ->has('digests', 3)
        );
    });

    it('only shows digests belonging to the user', function () {
        $myDigest = Digest::factory()->create(['user_id' => $this->user->id]);
        $otherDigest = Digest::factory()->create();

        $response = $this->actingAs($this->user)->get('/digests');

        $response->assertInertia(fn ($page) => $page
            ->has('digests', 1)
            ->where('digests.0.id', $myDigest->id)
        );
    });

    it('includes sources_count and destinations_count', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $sources = Source::factory(2)->create();
        $destinations = Destination::factory(2)->create(['user_id' => $this->user->id]);

        $digest->sources()->attach($sources);
        $digest->destinations()->attach($destinations);

        $response = $this->actingAs($this->user)->get('/digests');

        $response->assertInertia(fn ($page) => $page
            ->where('digests.0.sources_count', 2)
            ->where('digests.0.destinations_count', 2)
        );
    });

    it('requires authentication', function () {
        $this->get('/digests')->assertRedirect('/login');
    });
});

describe('create', function () {
    it('displays create digest form', function () {
        $response = $this->actingAs($this->user)->get('/digests/create');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('digests/create')
            ->has('destinations')
            ->has('timezones')
        );
    });

    it('passes user destinations grouped by type', function () {
        Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        Destination::factory()->discord()->create(['user_id' => $this->user->id]);
        Destination::factory()->email()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/digests/create');

        $response->assertInertia(fn ($page) => $page
            ->has('destinations.slack', 1)
            ->has('destinations.discord', 1)
            ->has('destinations.email', 1)
        );
    });

    it('passes maxRepos config value', function () {
        config(['isir.limits.github_repos_per_digest' => 15]);

        $response = $this->actingAs($this->user)->get('/digests/create');

        $response->assertInertia(fn ($page) => $page
            ->where('maxRepos', 15)
        );
    });
});

describe('store', function () {
    it('creates a daily digest', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'My Digest',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['laravel/framework', 'github.com/laravel/laravel'],
            'ai_enabled' => true,
            'include_versions_summary' => true,
        ]);

        $response->assertRedirect('/digests');

        $this->assertDatabaseHas('digests', [
            'user_id' => $this->user->id,
            'name' => 'My Digest',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'include_versions_summary' => true,
        ]);

        $digest = Digest::first();
        expect($digest->sources)->toHaveCount(2);
        expect($digest->sources->pluck('name')->toArray())->toContain('laravel/framework');
        expect($digest->sources->pluck('name')->toArray())->toContain('laravel/laravel');
    });

    it('creates a weekly digest', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Weekly Summary',
            'frequency' => 'weekly',
            'timezone' => 'America/New_York',
            'send_time' => '10:00',
            'send_day_of_week' => 1,
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertRedirect('/digests');

        $this->assertDatabaseHas('digests', [
            'name' => 'Weekly Summary',
            'frequency' => 'weekly',
            'send_day_of_week' => 1,
        ]);
    });

    it('attaches destinations', function () {
        $slackDest = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $discordDest = Destination::factory()->discord()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'With Destinations',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
            'slack_destination_id' => $slackDest->id,
            'discord_destination_id' => $discordDest->id,
        ]);

        $response->assertRedirect('/digests');

        $digest = Digest::first();
        expect($digest->destinations)->toHaveCount(2);
        expect($digest->destinations->pluck('id')->toArray())->toContain($slackDest->id);
        expect($digest->destinations->pluck('id')->toArray())->toContain($discordDest->id);
    });

    it('validates name is required', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertSessionHasErrors('name');
    });

    it('validates frequency is valid', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'monthly',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertSessionHasErrors('frequency');
    });

    it('validates timezone is valid', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'daily',
            'timezone' => 'Invalid/Timezone',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertSessionHasErrors('timezone');
    });

    it('validates send_day_of_week is required for weekly', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'weekly',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertSessionHasErrors('send_day_of_week');
    });

    it('validates source_urls is required', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => [],
        ]);

        $response->assertSessionHasErrors('source_urls');
    });

    it('validates source_urls format', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['not-a-valid-repo'],
        ]);

        $response->assertSessionHasErrors('source_urls.0');
    });

    it('validates repository exists on github', function () {
        // Override the mock to simulate non-existent repo
        $this->mock(GitHubService::class, function ($mock) {
            $mock->shouldReceive('repoExistsByUrl')
                ->with('nonexistent/repo')
                ->andReturn(['exists' => false, 'error' => null]);
        });

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['nonexistent/repo'],
        ]);

        $response->assertSessionHasErrors('source_urls.0');
    });

    it('validates destination belongs to user', function () {
        $otherDest = Destination::factory()->slack()->create();

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Test',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
            'slack_destination_id' => $otherDest->id,
        ]);

        $response->assertSessionHasErrors('slack_destination_id');
    });

    it('enforces maximum digests limit', function () {
        config(['isir.limits.digests_per_user' => 5]);

        Digest::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'One More',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertForbidden();
    });

    it('allows unlimited digests when limit is -1', function () {
        config(['isir.limits.digests_per_user' => -1]);

        Digest::factory(100)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'One More',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertRedirect('/digests');
    });

    it('accepts various github url formats', function () {
        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Multiple Formats',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => [
                'owner/repo',
                'github.com/owner2/repo2',
                'https://github.com/owner3/repo3',
                'https://github.com/owner4/repo4.git',
            ],
        ]);

        $response->assertRedirect('/digests');

        $digest = Digest::first();
        expect($digest->sources)->toHaveCount(4);
    });

    it('enforces maximum github repos per digest limit', function () {
        config(['isir.limits.github_repos_per_digest' => 3]);

        $repos = [];
        for ($i = 1; $i <= 4; $i++) {
            $repos[] = "owner/repo{$i}";
        }

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Too Many Repos',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => $repos,
        ]);

        $response->assertSessionHasErrors('source_urls');
    });

    it('allows repos at the limit', function () {
        config(['isir.limits.github_repos_per_digest' => 3]);

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'At Limit',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo1', 'owner/repo2', 'owner/repo3'],
        ]);

        $response->assertRedirect('/digests');
        expect(Digest::first()->sources)->toHaveCount(3);
    });

    it('allows unlimited repos when limit is -1', function () {
        config(['isir.limits.github_repos_per_digest' => -1]);

        $repos = [];
        for ($i = 1; $i <= 25; $i++) {
            $repos[] = "owner/repo{$i}";
        }

        $response = $this->actingAs($this->user)->post('/digests', [
            'name' => 'Unlimited Repos',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => $repos,
        ]);

        $response->assertRedirect('/digests');
        expect(Digest::first()->sources)->toHaveCount(25);
    });
});

describe('edit', function () {
    it('displays edit digest form', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/edit");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('digests/edit')
            ->has('digest')
            ->has('destinations')
            ->has('timezones')
            ->where('digest.id', $digest->id)
        );
    });

    it('prevents editing another users digest', function () {
        $otherDigest = Digest::factory()->create();

        $response = $this->actingAs($this->user)->get("/digests/{$otherDigest->id}/edit");

        $response->assertForbidden();
    });

    it('passes maxRepos config value', function () {
        config(['isir.limits.github_repos_per_digest' => 15]);

        $digest = Digest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/digests/{$digest->id}/edit");

        $response->assertInertia(fn ($page) => $page
            ->where('maxRepos', 15)
        );
    });
});

describe('update', function () {
    it('updates a digest', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $source = Source::factory()->create();
        $digest->sources()->attach($source);

        $response = $this->actingAs($this->user)->put("/digests/{$digest->id}", [
            'name' => 'Updated Name',
            'frequency' => 'weekly',
            'timezone' => 'America/Los_Angeles',
            'send_time' => '14:00',
            'send_day_of_week' => 5,
            'include_versions_summary' => true,
            'source_urls' => ['new-owner/new-repo'],
        ]);

        $response->assertRedirect('/digests');

        $digest->refresh();
        expect($digest->name)->toBe('Updated Name');
        expect($digest->frequency)->toBe('weekly');
        expect($digest->send_day_of_week)->toBe(5);
        expect($digest->include_versions_summary)->toBeTrue();
        expect($digest->sources->first()->name)->toBe('new-owner/new-repo');
    });

    it('syncs destinations', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $oldDest = Destination::factory()->slack()->create(['user_id' => $this->user->id]);
        $newDest = Destination::factory()->discord()->create(['user_id' => $this->user->id]);

        $digest->destinations()->attach($oldDest);

        $response = $this->actingAs($this->user)->put("/digests/{$digest->id}", [
            'name' => $digest->name,
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
            'discord_destination_id' => $newDest->id,
        ]);

        $response->assertRedirect('/digests');

        $digest->refresh();
        expect($digest->destinations)->toHaveCount(1);
        expect($digest->destinations->first()->id)->toBe($newDest->id);
    });

    it('prevents updating another users digest', function () {
        $otherDigest = Digest::factory()->create();

        $response = $this->actingAs($this->user)->put("/digests/{$otherDigest->id}", [
            'name' => 'Hacked',
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => ['owner/repo'],
        ]);

        $response->assertForbidden();
    });

    it('enforces maximum github repos per digest limit on update', function () {
        config(['isir.limits.github_repos_per_digest' => 3]);

        $digest = Digest::factory()->create(['user_id' => $this->user->id]);

        $repos = [];
        for ($i = 1; $i <= 4; $i++) {
            $repos[] = "owner/repo{$i}";
        }

        $response = $this->actingAs($this->user)->put("/digests/{$digest->id}", [
            'name' => $digest->name,
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => $repos,
        ]);

        $response->assertSessionHasErrors('source_urls');
    });

    it('allows unlimited repos on update when limit is -1', function () {
        config(['isir.limits.github_repos_per_digest' => -1]);

        $digest = Digest::factory()->create(['user_id' => $this->user->id]);

        $repos = [];
        for ($i = 1; $i <= 25; $i++) {
            $repos[] = "owner/repo{$i}";
        }

        $response = $this->actingAs($this->user)->put("/digests/{$digest->id}", [
            'name' => $digest->name,
            'frequency' => 'daily',
            'timezone' => 'UTC',
            'send_time' => '09:00',
            'source_urls' => $repos,
        ]);

        $response->assertRedirect('/digests');
        expect($digest->refresh()->sources)->toHaveCount(25);
    });
});

describe('destroy', function () {
    it('deletes a digest', function () {
        $digest = Digest::factory()->create(['user_id' => $this->user->id]);
        $source = Source::factory()->create();
        $dest = Destination::factory()->create(['user_id' => $this->user->id]);
        $digest->sources()->attach($source);
        $digest->destinations()->attach($dest);

        $response = $this->actingAs($this->user)->delete("/digests/{$digest->id}");

        $response->assertRedirect('/digests');
        $this->assertDatabaseMissing('digests', ['id' => $digest->id]);
        $this->assertDatabaseMissing('digest_source', ['digest_id' => $digest->id]);
        $this->assertDatabaseMissing('digest_destination', ['digest_id' => $digest->id]);
    });

    it('prevents deleting another users digest', function () {
        $otherDigest = Digest::factory()->create();

        $response = $this->actingAs($this->user)->delete("/digests/{$otherDigest->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('digests', ['id' => $otherDigest->id]);
    });
});

describe('toggle', function () {
    it('toggles digest enabled status', function () {
        $digest = Digest::factory()->create([
            'user_id' => $this->user->id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($this->user)->patch("/digests/{$digest->id}/toggle");

        $response->assertRedirect();
        expect($digest->fresh()->is_enabled)->toBeFalse();

        $this->actingAs($this->user)->patch("/digests/{$digest->id}/toggle");
        expect($digest->fresh()->is_enabled)->toBeTrue();
    });

    it('prevents toggling another users digest', function () {
        $otherDigest = Digest::factory()->create(['is_enabled' => true]);

        $response = $this->actingAs($this->user)->patch("/digests/{$otherDigest->id}/toggle");

        $response->assertForbidden();
        expect($otherDigest->fresh()->is_enabled)->toBeTrue();
    });
});
