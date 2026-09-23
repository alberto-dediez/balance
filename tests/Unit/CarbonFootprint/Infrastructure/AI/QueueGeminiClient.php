<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\Shared\Infrastructure\AI\Gemini\GeminiClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class QueueGeminiClient implements GeminiClient
{
    private int $calls = 0;

    /** @param list<array<mixed>> $responses */
    public function __construct(private array $responses) {}

    /** @return array<mixed> */
    public function send(GeminiPrompt $prompt): array
    {
        ++$this->calls;

        return array_shift($this->responses) ?? [];
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
