<?php

namespace App\Actions;

use App\Enums\DeliveryAttemptStatus;
use App\Enums\DestinationType;
use App\Models\Destination;
use App\Models\DigestRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DeliverDigestRun
{
    /**
     * Deliver a digest run to all configured destinations.
     */
    public function __invoke(DigestRun $digestRun): void
    {
        $digest = $digestRun->digest;
        $destinations = $digest->destinations()->where('is_enabled', true)->get();

        foreach ($destinations as $destination) {
            $this->deliverToDestination($digestRun, $destination);
        }
    }

    /**
     * Get the URL to view this digest run online.
     */
    private function getRunUrl(DigestRun $digestRun): string
    {
        return route('digests.runs.show', [$digestRun->digest, $digestRun]);
    }

    private function deliverToDestination(DigestRun $digestRun, Destination $destination): void
    {
        $attempt = $digestRun->deliveryAttempts()->create([
            'destination_id' => $destination->id,
            'status' => DeliveryAttemptStatus::Pending->value,
        ]);

        try {
            $result = match ($destination->type) {
                DestinationType::Slack->value => $this->deliverToSlack($digestRun, $destination),
                DestinationType::Discord->value => $this->deliverToDiscord($digestRun, $destination),
                DestinationType::Email->value => $this->deliverToEmail($digestRun, $destination),
                default => throw new \InvalidArgumentException("Unknown destination type: {$destination->type}"),
            };

            $attempt->update([
                'status' => DeliveryAttemptStatus::Sent->value,
                'sent_at' => now(),
                'provider_message_id' => $result['message_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to deliver digest run {$digestRun->id} to destination {$destination->id}", [
                'error' => $e->getMessage(),
            ]);

            $attempt->update([
                'status' => DeliveryAttemptStatus::Failed->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{message_id: string|null}
     */
    private function deliverToSlack(DigestRun $digestRun, Destination $destination): array
    {
        $webhookUrl = $destination->config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            throw new \InvalidArgumentException('Slack webhook URL not configured');
        }

        $response = Http::post($webhookUrl, [
            'text' => $this->formatForSlack($digestRun),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Slack webhook failed: {$response->status()}");
        }

        return ['message_id' => null];
    }

    /**
     * @return array{message_id: string|null}
     */
    private function deliverToDiscord(DigestRun $digestRun, Destination $destination): array
    {
        $webhookUrl = $destination->config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            throw new \InvalidArgumentException('Discord webhook URL not configured');
        }

        $response = Http::post($webhookUrl, [
            'content' => $this->formatForDiscord($digestRun),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Discord webhook failed: {$response->status()}");
        }

        return ['message_id' => $response->json('id')];
    }

    /**
     * @return array{message_id: string|null}
     */
    private function deliverToEmail(DigestRun $digestRun, Destination $destination): array
    {
        $email = $destination->config['email'] ?? null;

        if (! $email) {
            throw new \InvalidArgumentException('Email address not configured');
        }

        $digest = $digestRun->digest;
        $url = $this->getRunUrl($digestRun);
        $content = "Your digest \"{$digest->name}\" is now available.\n\nView it here: {$url}";

        Mail::raw($content, function ($message) use ($email, $digest) {
            $message->to($email)
                ->subject("{$digest->name} - Digest Update");
        });

        return ['message_id' => null];
    }

    private function formatForSlack(DigestRun $digestRun): string
    {
        $digest = $digestRun->digest;
        $url = $this->getRunUrl($digestRun);

        return "Your digest \"{$digest->name}\" is now available.\n\nView it here: {$url}";
    }

    private function formatForDiscord(DigestRun $digestRun): string
    {
        $digest = $digestRun->digest;
        $url = $this->getRunUrl($digestRun);

        return "Your digest \"{$digest->name}\" is now available.\n\nView it here: {$url}";
    }
}
