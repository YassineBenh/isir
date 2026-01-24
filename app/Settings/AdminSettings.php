<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AdminSettings extends Settings
{
    public bool $registration_enabled;

    public static function group(): string
    {
        return 'admin';
    }
}
