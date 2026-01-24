<?php

namespace App\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\PendingRequest;

class AIService
{
    /**
     * Check if AI is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->getProvider() !== null
            && $this->getModel() !== null
            && $this->getApiKey() !== null;
    }

    /**
     * Get a prepared Prism text generation instance.
     */
    public function text(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('AI is not configured. Please set AI_PROVIDER, AI_MODEL, and AI_API_KEY environment variables.');
        }

        return Prism::text()
            ->using($this->getPrismProvider(), $this->getModel())
            ->usingProviderConfig(['api_key' => $this->getApiKey()]);
    }

    /**
     * Get the configured provider name.
     */
    public function getProvider(): ?string
    {
        return config('services.ai.provider');
    }

    /**
     * Get the configured model name.
     */
    public function getModel(): ?string
    {
        return config('services.ai.model');
    }

    /**
     * Get the configured API key.
     */
    public function getApiKey(): ?string
    {
        return config('services.ai.api_key');
    }

    /**
     * Get the Prism Provider enum from the configured provider string.
     */
    private function getPrismProvider(): Provider
    {
        return match ($this->getProvider()) {
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'ollama' => Provider::Ollama,
            'gemini' => Provider::Gemini,
            'mistral' => Provider::Mistral,
            'groq' => Provider::Groq,
            'deepseek' => Provider::DeepSeek,
            'xai' => Provider::XAI,
            default => throw new \RuntimeException("Unknown AI provider: {$this->getProvider()}"),
        };
    }
}
