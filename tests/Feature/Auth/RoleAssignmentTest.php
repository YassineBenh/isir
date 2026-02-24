<?php

use App\Models\User;

test('first registered user gets admin role', function () {
    // Ensure no users exist
    User::query()->delete();

    $response = $this->post(route('register.store'), [
        'name' => 'First User',
        'email' => 'first@example.com',
        'timezone' => 'UTC',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'first@example.com')->first();
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('user'))->toBeFalse();
});

test('subsequent registered users get user role', function () {
    // Create first user with admin role
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Ensure registration is enabled for this test
    $settings = app(\App\Settings\AdminSettings::class);
    $settings->registration_enabled = true;
    $settings->save();

    $response = $this->post(route('register.store'), [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'timezone' => 'Europe/Paris',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'second@example.com')->first();
    expect($user->hasRole('user'))->toBeTrue();
    expect($user->hasRole('admin'))->toBeFalse();
    expect($user->timezone)->toBe('Europe/Paris');
});
