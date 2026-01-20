<?php

namespace App\Rules;

use App\Services\GitHubService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GitHubRepoExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $github = app(GitHubService::class);
        $result = $github->repoExistsByUrl($value);

        if (! $result['exists']) {
            $parsed = GitHubRepoUrl::parse($value);
            $repoName = $parsed ? "{$parsed['owner']}/{$parsed['repo']}" : $value;

            // Always show user-friendly message, even for API errors (rate limit, etc.)
            $fail("The repository '{$repoName}' does not exist or is private.");
        }
    }
}
