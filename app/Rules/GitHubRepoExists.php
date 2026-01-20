<?php

namespace App\Rules;

use App\Models\Source;
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

        // Normalize URL to owner/repo format
        $ownerRepo = GitHubRepoUrl::normalize($value);

        if ($ownerRepo === null) {
            $fail('The :attribute must be a valid GitHub repository URL.');

            return;
        }

        // Skip GitHub API call if repo already exists in database (already validated)
        if (Source::query()->where('name', strtolower($ownerRepo))->exists()) {
            return;
        }

        $github = app(GitHubService::class);
        $result = $github->repoExistsByUrl($value);

        if (! $result['exists']) {
            // Always show user-friendly message, even for API errors (rate limit, etc.)
            $fail("The repository '{$ownerRepo}' does not exist or is private.");
        }
    }
}
