<?php

use App\Models\User;
use App\Settings\AdminSettings;

test('admin can access admin settings page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('admin.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('settings/admin')
        ->has('settings.registration_enabled')
    );
});

test('non-admin cannot access admin settings page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->get(route('admin.edit'));

    $response->assertForbidden();
});

test('admin can toggle registration setting', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $settings = app(AdminSettings::class);
    expect($settings->registration_enabled)->toBeFalse();

    $response = $this->actingAs($admin)->patch(route('admin.update'), [
        'registration_enabled' => true,
    ]);

    $response->assertRedirect(route('admin.edit'));

    $settings->refresh();
    expect($settings->registration_enabled)->toBeTrue();
});

test('non-admin cannot update admin settings', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)->patch(route('admin.update'), [
        'registration_enabled' => true,
    ]);

    $response->assertForbidden();

    $settings = app(AdminSettings::class);
    expect($settings->registration_enabled)->toBeFalse();
});

test('registration page redirects to login when registration is disabled', function () {
    User::factory()->create();

    $settings = app(AdminSettings::class);
    $settings->registration_enabled = false;
    $settings->save();

    $response = $this->get(route('register'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Registration is currently disabled.');
});

test('registration page is accessible when registration is enabled', function () {
    User::factory()->create();

    $settings = app(AdminSettings::class);
    $settings->registration_enabled = true;
    $settings->save();

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('login page shows registration link when enabled', function () {
    User::factory()->create();

    $settings = app(AdminSettings::class);
    $settings->registration_enabled = true;
    $settings->save();

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('canRegister', true)
    );
});

test('login page hides registration link when disabled', function () {
    User::factory()->create();

    $settings = app(AdminSettings::class);
    $settings->registration_enabled = false;
    $settings->save();

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('canRegister', false)
    );
});

test('registration page is accessible for first user when registration is disabled', function () {
    $settings = app(AdminSettings::class);
    $settings->registration_enabled = false;
    $settings->save();

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('login page shows registration link for first user when registration is disabled', function () {
    $settings = app(AdminSettings::class);
    $settings->registration_enabled = false;
    $settings->save();

    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('canRegister', true)
    );
});

test('users cannot register when registration is disabled', function () {
    // First, create an admin user so registration is not the "first user" case
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $settings = app(AdminSettings::class);
    $settings->registration_enabled = false;
    $settings->save();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'UTC',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // The POST to /register should be blocked when registration is disabled
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
