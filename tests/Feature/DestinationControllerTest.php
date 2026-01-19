<?php

use App\Models\Destination;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('index', function () {
    it('displays destinations index page', function () {
        $destinations = Destination::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/destinations');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('destinations/index')
            ->has('destinations', 3)
        );
    });

    it('only shows destinations belonging to the user', function () {
        $myDestination = Destination::factory()->create(['user_id' => $this->user->id]);
        $otherDestination = Destination::factory()->create();

        $response = $this->actingAs($this->user)->get('/destinations');

        $response->assertInertia(fn ($page) => $page
            ->has('destinations', 1)
            ->where('destinations.0.id', $myDestination->id)
        );
    });

    it('requires authentication', function () {
        $this->get('/destinations')->assertRedirect('/login');
    });
});

describe('create', function () {
    it('displays create destination form', function () {
        $response = $this->actingAs($this->user)->get('/destinations/create');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page->component('destinations/create'));
    });
});

describe('store', function () {
    it('creates a slack destination', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'slack',
            'name' => '#releases',
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);

        $response->assertRedirect('/destinations');

        $this->assertDatabaseHas('destinations', [
            'user_id' => $this->user->id,
            'type' => 'slack',
            'name' => '#releases',
        ]);

        $destination = Destination::first();
        expect($destination->config['webhook_url'])->toBe('https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX');
        expect($destination->is_enabled)->toBeTrue();
    });

    it('creates a discord destination', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'discord',
            'name' => '#updates',
            'webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz',
        ]);

        $response->assertRedirect('/destinations');

        $this->assertDatabaseHas('destinations', [
            'user_id' => $this->user->id,
            'type' => 'discord',
            'name' => '#updates',
        ]);
    });

    it('creates an email destination', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'email',
            'name' => 'Personal Email',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/destinations');

        $destination = Destination::first();
        expect($destination->type)->toBe('email');
        expect($destination->config['email'])->toBe('test@example.com');
    });

    it('validates type is required', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'name' => 'Test',
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);

        $response->assertSessionHasErrors('type');
    });

    it('validates type is valid', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'invalid',
            'name' => 'Test',
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);

        $response->assertSessionHasErrors('type');
    });

    it('validates webhook_url is required for slack', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'slack',
            'name' => '#releases',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('validates webhook_url is required for discord', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'discord',
            'name' => '#updates',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('validates email is required for email type', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'email',
            'name' => 'Personal',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('validates slack webhook url format', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'slack',
            'name' => '#releases',
            'webhook_url' => 'https://example.com/invalid',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('validates discord webhook url format', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'discord',
            'name' => '#updates',
            'webhook_url' => 'https://example.com/invalid',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('rejects slack webhook url for discord type', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'discord',
            'name' => '#updates',
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('rejects discord webhook url for slack type', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'slack',
            'name' => '#releases',
            'webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz',
        ]);

        $response->assertSessionHasErrors('webhook_url');
    });

    it('validates email format', function () {
        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'email',
            'name' => 'Personal',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('enforces maximum destinations limit', function () {
        config(['isir.limits.destinations_per_user' => 5]);

        Destination::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'email',
            'name' => 'One More',
            'email' => 'test@example.com',
        ]);

        $response->assertForbidden();
    });

    it('allows unlimited destinations when limit is -1', function () {
        config(['isir.limits.destinations_per_user' => -1]);

        Destination::factory(100)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->post('/destinations', [
            'type' => 'email',
            'name' => 'One More',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/destinations');
    });
});

describe('edit', function () {
    it('displays edit destination form', function () {
        $destination = Destination::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/destinations/{$destination->id}/edit");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('destinations/edit')
            ->has('destination')
            ->where('destination.id', $destination->id)
        );
    });

    it('prevents editing another users destination', function () {
        $otherDestination = Destination::factory()->create();

        $response = $this->actingAs($this->user)->get("/destinations/{$otherDestination->id}/edit");

        $response->assertForbidden();
    });
});

describe('update', function () {
    it('updates a destination', function () {
        $destination = Destination::factory()->slack()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->put("/destinations/{$destination->id}", [
            'type' => 'slack',
            'name' => 'Updated Name',
            'webhook_url' => 'https://hooks.slack.com/services/TNEWTEAM/BNEWBOT/NewWebhookToken123',
        ]);

        $response->assertRedirect('/destinations');

        $destination->refresh();
        expect($destination->name)->toBe('Updated Name');
        expect($destination->config['webhook_url'])->toBe('https://hooks.slack.com/services/TNEWTEAM/BNEWBOT/NewWebhookToken123');
    });

    it('prevents updating another users destination', function () {
        $otherDestination = Destination::factory()->create();

        $response = $this->actingAs($this->user)->put("/destinations/{$otherDestination->id}", [
            'type' => 'slack',
            'name' => 'Hacked',
            'webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
        ]);

        $response->assertForbidden();
    });
});

describe('destroy', function () {
    it('deletes a destination', function () {
        $destination = Destination::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->delete("/destinations/{$destination->id}");

        $response->assertRedirect('/destinations');
        $this->assertDatabaseMissing('destinations', ['id' => $destination->id]);
    });

    it('prevents deleting another users destination', function () {
        $otherDestination = Destination::factory()->create();

        $response = $this->actingAs($this->user)->delete("/destinations/{$otherDestination->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('destinations', ['id' => $otherDestination->id]);
    });
});

describe('toggle', function () {
    it('toggles destination enabled status', function () {
        $destination = Destination::factory()->create([
            'user_id' => $this->user->id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($this->user)->patch("/destinations/{$destination->id}/toggle");

        $response->assertRedirect();
        expect($destination->fresh()->is_enabled)->toBeFalse();

        $this->actingAs($this->user)->patch("/destinations/{$destination->id}/toggle");
        expect($destination->fresh()->is_enabled)->toBeTrue();
    });

    it('prevents toggling another users destination', function () {
        $otherDestination = Destination::factory()->create(['is_enabled' => true]);

        $response = $this->actingAs($this->user)->patch("/destinations/{$otherDestination->id}/toggle");

        $response->assertForbidden();
        expect($otherDestination->fresh()->is_enabled)->toBeTrue();
    });
});
