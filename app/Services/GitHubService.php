<?php

namespace App\Services;

use App\Rules\GitHubRepoUrl;
use Github\Exception\RuntimeException;
use GrahamCampbell\GitHub\GitHubManager;

class GitHubService
{
    public function __construct(
        private readonly GitHubManager $github,
    ) {}

    /**
     * Check if a GitHub repository exists.
     *
     * @return array{exists: bool, error: string|null}
     */
    public function repoExists(string $owner, string $repo): array
    {
        try {
            $this->github->repo()->show($owner, $repo);

            return ['exists' => true, 'error' => null];
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                return ['exists' => false, 'error' => null];
            }

            // Rate limited or other API error
            return ['exists' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if a GitHub repository exists by URL or owner/repo format.
     *
     * @return array{exists: bool, error: string|null}
     */
    public function repoExistsByUrl(string $url): array
    {
        $parsed = GitHubRepoUrl::parse($url);

        if ($parsed === null) {
            return ['exists' => false, 'error' => 'Invalid GitHub repository URL format.'];
        }

        return $this->repoExists($parsed['owner'], $parsed['repo']);
    }

    /**
     * Validate multiple repository URLs and return any that don't exist.
     *
     * @param  array<string>  $urls
     * @return array{valid: bool, errors: array<string, string>}
     */
    public function validateRepositories(array $urls): array
    {
        $errors = [];

        foreach ($urls as $index => $url) {
            $result = $this->repoExistsByUrl($url);

            if (! $result['exists']) {
                $parsed = GitHubRepoUrl::parse($url);
                $repoName = $parsed ? "{$parsed['owner']}/{$parsed['repo']}" : $url;

                $errors["source_urls.{$index}"] = $result['error']
                    ?? "The repository '{$repoName}' does not exist or is private.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Fetch all releases for a repository.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchReleases(string $owner, string $repo): array
    {
        return $this->github->repo()->releases()->all($owner, $repo);
    }
}
