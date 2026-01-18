<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DiscordWebhookUrl implements ValidationRule
{
    /**
     * Discord webhook URL pattern.
     * Format: https://discord.com/api/webhooks/123456789012345678/abcdefghijklmnopqrstuvwxyz
     * Also supports: https://discordapp.com/api/webhooks/...
     */
    private const PATTERN = '/^https:\/\/(discord\.com|discordapp\.com)\/api\/webhooks\/\d+\/[A-Za-z0-9_-]+$/';

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('The :attribute must be a valid Discord webhook URL (https://discord.com/api/webhooks/...).');
        }
    }
}
