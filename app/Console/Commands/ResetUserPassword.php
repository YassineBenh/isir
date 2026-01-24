<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email : The email of the user}';

    protected $description = 'Reset the password of a user by their email';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email [{$email}] not found.");

            return self::FAILURE;
        }

        $newPassword = password(
            label: 'Enter the new password',
            required: true,
        );

        $user->update(['password' => $newPassword]);

        $this->info("Password for user [{$email}] has been reset successfully.");

        return self::SUCCESS;
    }
}
