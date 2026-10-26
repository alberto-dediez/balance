<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\Shared\Infrastructure\AI\AiClient;
use App\Shared\Infrastructure\AI\AiPrompt;

final class QueueGeminiClient implements AiClient
{
    private int $calls = 0;

    /** @param list<array<mixed>> $responses */
    public function __construct(private array $responses) {}

    /** @return array<mixed> */
    public function send(AiPrompt $prompt): array
    {
        ++$this->calls;

        return array_shift($this->responses) ?? [];
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
