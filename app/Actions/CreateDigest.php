<?php

namespace App\Actions;

use App\Models\Digest;
use App\Models\User;

class CreateDigest
{
    public function __construct(
        private FindOrCreateSource $findOrCreateSource
    ) {}

    /**
     * Create a new digest for the user.
     *
     * @param  array{
     *     name: string,
     *     frequency: string,
     *     timezone: string,
     *     send_time: string,
     *     send_day_of_week?: int|null,
     *     is_enabled?: bool,
     *     ai_enabled?: bool,
     *     include_versions_summary?: bool,
     *     source_urls: array<string>,
     *     slack_destination_id?: int|null,
     *     discord_destination_id?: int|null,
     *     email_destination_id?: int|null,
     * }  $data
     */
    public function __invoke(User $user, array $data): Digest
    {
        $digest = $user->digests()->create([
            'name' => $data['name'],
            'frequency' => $data['frequency'],
            'timezone' => $data['timezone'],
            'send_time' => $data['send_time'],
            'send_day_of_week' => $data['send_day_of_week'] ?? null,
            'is_enabled' => $data['is_enabled'] ?? true,
            'ai_enabled' => $data['ai_enabled'] ?? true,
            'include_versions_summary' => $data['include_versions_summary'] ?? false,
        ]);

        // Attach sources
        $sourceIds = $this->findOrCreateSource->fromUrls($data['source_urls']);
        $digest->sources()->attach($sourceIds);

        // Attach destinations
        $destinationIds = array_filter([
            $data['slack_destination_id'] ?? null,
            $data['discord_destination_id'] ?? null,
            $data['email_destination_id'] ?? null,
        ]);

        if (! empty($destinationIds)) {
            $digest->destinations()->attach($destinationIds);
        }

        return $digest->load(['sources', 'destinations']);
    }
}
