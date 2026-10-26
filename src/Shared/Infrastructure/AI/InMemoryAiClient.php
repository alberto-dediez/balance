<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI;

use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryAiClient implements AiClient
{
    private ?AiPrompt $lastPrompt = null;

    /** @param array<mixed> $response */
    public function __construct(private array $response = []) {}

    /** @param array<mixed> $response */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array
    {
        $this->lastPrompt = $prompt;

        return $this->response;
    }

    public function lastPrompt(): ?AiPrompt
    {
        return $this->lastPrompt;
    }
}
