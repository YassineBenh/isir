<?php

use App\Models\User;
use App\Settings\AdminSettings;
use Inertia\Testing\AssertableInertia;

test('registration screen can be rendered', function () {
    $settings = app(AdminSettings::class);
    expect($settings->registration_enabled)->toBeFalse();

    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/register')
            ->has('timezones')
            ->where('timezones.0', 'UTC')
        );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'America/New_York',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    expect(User::where('email', 'test@example.com')->first()?->timezone)
        ->toBe('America/New_York');
});
