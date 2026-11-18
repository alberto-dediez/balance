<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\AiClient;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiResponse;
use App\Shared\Application\Ai\AiResponseException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class ProviderAiClient implements AiClient
{
    /** @param iterable<AiProviderClient> $providers */
    public function __construct(
        #[AutowireIterator('shared.ai.provider')]
        private iterable $providers,
        private string $provider,
    ) {}

    public function send(AiPrompt $prompt): AiResponse
    {
        foreach ($this->providers as $client) {
            if ($client->provider()->value === $this->provider) {
                return $client->send($prompt);
            }
        }

        throw AiResponseException::providerNotConfigured($this->provider);
    }
}
