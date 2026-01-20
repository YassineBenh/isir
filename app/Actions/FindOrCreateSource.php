<?php

namespace App\Actions;

use App\Models\Source;
use App\Rules\GitHubRepoUrl;

class FindOrCreateSource
{
    /**
     * Find or create a source from a GitHub URL or owner/repo string.
     */
    public function __invoke(string $url): Source
    {
        $parsed = GitHubRepoUrl::parse($url);

        if ($parsed === null) {
            throw new \InvalidArgumentException("Invalid GitHub URL: {$url}");
        }

        $owner = strtolower($parsed['owner']);
        $repo = strtolower($parsed['repo']);

        return Source::firstOrCreate(
            ['canonical_key' => "github:{$owner}/{$repo}"],
            [
                'type' => 'github_repo',
                'name' => "{$owner}/{$repo}",
                'url' => "https://github.com/{$owner}/{$repo}",
                'config' => [
                    'owner' => $owner,
                    'repo' => $repo,
                ],
                'is_enabled' => true,
            ]
        );
    }

    /**
     * Find or create sources from an array of GitHub URLs.
     *
     * @param  array<string>  $urls
     * @return array<int> Source IDs
     */
    public function fromUrls(array $urls): array
    {
        $sourceIds = [];

        foreach ($urls as $url) {
            $source = $this($url);
            $sourceIds[] = $source->id;
        }

        return $sourceIds;
    }
}
