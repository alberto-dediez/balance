<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

use App\Shared\Infrastructure\AI\Anthropic\AnthropicHttpClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiHttpClient;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class ProviderAiClient implements AiClient
{
    private readonly AiProvider $provider;

    public function __construct(
        private readonly GeminiHttpClient $geminiClient,
        private readonly AnthropicHttpClient $anthropicClient,
        string $provider,
    ) {
        $this->provider = AiProvider::from($provider);
    }

    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array
    {
        return match ($this->provider) {
            AiProvider::Gemini => $this->geminiClient->send($prompt),
            AiProvider::Anthropic => $this->anthropicClient->send($prompt),
        };
    }
}
