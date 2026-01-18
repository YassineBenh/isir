<?php

namespace App\Actions;

use App\Models\Destination;
use App\Models\User;

class CreateDestination
{
    /**
     * Create a new destination for the user.
     *
     * @param  array{type: string, name: string, webhook_url?: string|null, email?: string|null}  $data
     */
    public function __invoke(User $user, array $data): Destination
    {
        return $user->destinations()->create([
            'type' => $data['type'],
            'name' => $data['name'],
            'config' => $this->buildConfig($data),
            'is_enabled' => true,
        ]);
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
