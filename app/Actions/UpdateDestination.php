<?php

namespace App\Actions;

use App\Models\Destination;

class UpdateDestination
{
    /**
     * Update an existing destination.
     *
     * @param  array{type: string, name: string, webhook_url?: string|null, email?: string|null}  $data
     */
    public function __invoke(Destination $destination, array $data): Destination
    {
        $destination->update([
            'type' => $data['type'],
            'name' => $data['name'],
            'config' => $this->buildConfig($data),
        ]);

        return $destination->refresh();
    }

    /**
     * Build the config array based on the destination type.
     *
     * @param  array{type: string, name: string, webhook_url?: string|null, email?: string|null}  $data
     * @return array<string, string>
     */
    private function buildConfig(array $data): array
    {
        return match ($data['type']) {
            'slack', 'discord' => ['webhook_url' => $data['webhook_url'] ?? ''],
            'email' => ['email' => $data['email'] ?? ''],
            default => [],
        };
    }
}
