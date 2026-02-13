<?php

namespace App\Services;

use Laravel\Ai\Enums\Lab;

class AIService
{
    /**
     * Check if AI is properly configured.
     */
    public function isConfigured(): bool
    {
        $provider = $this->getProvider();

        if ($provider === null) {
            return false;
        }

        if ($provider === Lab::Ollama->value) {
            $url = config('ai.providers.ollama.url');

            return is_string($url) && trim($url) !== '';
        }

        $apiKey = config("ai.providers.{$provider}.key");

        return is_string($apiKey) && trim($apiKey) !== '';
    }

    /**
     * Generate a digest summary from prompt content.
     */
    public function summarizeDigest(string $prompt): string
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('AI is not configured. Set the API key for your configured provider in config/ai.php (for example OPENAI_API_KEY).');
        }

        $response = DigestSummaryAgent::make()->prompt(
            $prompt,
            provider: $this->getLabProvider(),
            model: $this->getModel(),
        );

        return $response->text;
    }

    /**
     * Get the configured provider name.
     */
    public function getProvider(): ?string
    {
        $provider = config('ai.default');

        if ($provider instanceof Lab) {
            return $provider->value;
        }

        if (! is_string($provider) || trim($provider) === '') {
            return null;
        }

        return $provider;
    }

    /**
     * Get the configured model name.
     */
    public function getModel(): ?string
    {
        $model = config('ai.model');

        return is_string($model) && trim($model) !== '' ? $model : null;
    }

    /**
     * Get the AI provider enum from the configured provider string.
     */
    private function getLabProvider(): Lab
    {
        $provider = $this->getProvider();

        if ($provider === null) {
            throw new \RuntimeException('AI provider is not configured.');
        }

        try {
            return Lab::from($provider);
        } catch (\ValueError $e) {
            throw new \RuntimeException("Unknown AI provider: {$provider}", previous: $e);
        }
    }
}
