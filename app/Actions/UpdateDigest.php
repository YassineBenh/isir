<?php

namespace App\Actions;

use App\Models\Digest;

class UpdateDigest
{
    public function __construct(
        private FindOrCreateSource $findOrCreateSource
    ) {}

    /**
     * Update an existing digest.
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
    public function __invoke(Digest $digest, array $data): Digest
    {
        $digest->update([
            'name' => $data['name'],
            'frequency' => $data['frequency'],
            'timezone' => $data['timezone'],
            'send_time' => $data['send_time'],
            'send_day_of_week' => $data['send_day_of_week'] ?? null,
            'is_enabled' => $data['is_enabled'] ?? $digest->is_enabled,
            'ai_enabled' => $data['ai_enabled'] ?? $digest->ai_enabled,
            'include_versions_summary' => $data['include_versions_summary'] ?? $digest->include_versions_summary,
        ]);

        // Sync sources
        $sourceIds = $this->findOrCreateSource->fromUrls($data['source_urls']);
        $digest->sources()->sync($sourceIds);

        // Sync destinations
        $destinationIds = array_filter([
            $data['slack_destination_id'] ?? null,
            $data['discord_destination_id'] ?? null,
            $data['email_destination_id'] ?? null,
        ]);

        $digest->destinations()->sync($destinationIds);

        return $digest->refresh()->load(['sources', 'destinations']);
    }
}
