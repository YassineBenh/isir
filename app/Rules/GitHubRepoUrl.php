<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubRepoUrl implements ValidationRule
{
    /**
     * The regex pattern for GitHub repo URLs and owner/repo format.
     *
     * Accepts:
     * - https://github.com/owner/repo
     * - https://github.com/owner/repo.git
     * - http://github.com/owner/repo
     * - github.com/owner/repo
     * - owner/repo
     */
    private const PATTERN = '/^(?:(?:https?:\/\/)?github\.com\/)?([a-zA-Z0-9](?:[a-zA-Z0-9\-]*[a-zA-Z0-9])?)\/([\w.\-]+?)(?:\.git)?$/';

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('The :attribute must be a valid GitHub repository URL or owner/repo format.');
        }
    }

    /**
     * Parse a GitHub URL or owner/repo string into [owner, repo].
     *
     * @return array{owner: string, repo: string}|null
     */
    public static function parse(string $value): ?array
    {
        if (preg_match(self::PATTERN, $value, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * Normalize a GitHub URL to canonical owner/repo format.
     */
    public static function normalize(string $value): ?string
    {
        $parsed = self::parse($value);

        if ($parsed === null) {
            return null;
        }

        return $parsed['owner'].'/'.$parsed['repo'];
    }
}
