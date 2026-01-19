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

        $canonicalKey = 'github:'.$parsed['owner'].'/'.$parsed['repo'];

        return Source::firstOrCreate(
            ['canonical_key' => $canonicalKey],
            [
                'type' => 'github_repo',
                'name' => $parsed['owner'].'/'.$parsed['repo'],
                'url' => 'https://github.com/'.$parsed['owner'].'/'.$parsed['repo'],
                'config' => [
                    'owner' => $parsed['owner'],
                    'repo' => $parsed['repo'],
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
