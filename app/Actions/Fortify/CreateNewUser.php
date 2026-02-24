<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Settings\AdminSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Features;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $this->ensureRegistrationIsEnabled();

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $isFirstUser = User::count() === 0;

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'timezone' => $input['timezone'] ?? 'UTC',
            'password' => $input['password'],
        ]);

        $user->assignRole($isFirstUser ? 'admin' : 'user');

        return $user;
    }

    /**
     * Ensure registration is enabled.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function ensureRegistrationIsEnabled(): void
    {
        // Always allow first user to register (bootstrap admin)
        if (User::count() === 0) {
            return;
        }

        if (! Features::enabled(Features::registration())) {
            throw ValidationException::withMessages([
                'email' => 'Registration is currently disabled.',
            ]);
        }

        if (! app(AdminSettings::class)->registration_enabled) {
            throw ValidationException::withMessages([
                'email' => 'Registration is currently disabled.',
            ]);
        }
    }
}
