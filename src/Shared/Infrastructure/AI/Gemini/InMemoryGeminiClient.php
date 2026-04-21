<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\AI\Gemini;

use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryGeminiClient implements GeminiClient
{
    private ?GeminiPrompt $lastPrompt = null;

    /** @param array<mixed> $response */
    public function __construct(private readonly array $response = []) {}

    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array
    {
        $this->lastPrompt = $prompt;

        return $this->response;
    }

    public function lastPrompt(): ?GeminiPrompt
    {
        return $this->lastPrompt;
    }
}
