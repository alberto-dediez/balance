<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ai;

use App\Shared\Application\Ai\Agent\AiAgentClient;
use App\Shared\Application\Ai\Agent\AiAgentRequest;
use App\Shared\Application\Ai\Agent\AiAgentStep;
use App\Shared\Application\Ai\AiResponseException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class ProviderAiAgentClient implements AiAgentClient
{
    /** @param iterable<AiAgentProviderClient> $providers */
    public function __construct(
        #[AutowireIterator('shared.ai.agent_provider')]
        private iterable $providers,
        private string $provider,
    ) {}

    public function step(AiAgentRequest $request): AiAgentStep
    {
        foreach ($this->providers as $client) {
            if ($client->provider()->value === $this->provider) {
                return $client->step($request);
            }
        }

        throw AiResponseException::providerNotConfigured($this->provider);
    }
}
