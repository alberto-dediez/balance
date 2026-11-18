<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\AI;

use App\Shared\Application\Ai\AiClient;
use App\Shared\Application\Ai\AiPrompt;
use App\Shared\Application\Ai\AiResponse;
use App\Shared\Application\Ai\AiUsage;

final class QueueAiClient implements AiClient
{
    private int $calls = 0;

    /** @param list<array<mixed>> $responses */
    public function __construct(private array $responses) {}

    public function send(AiPrompt $prompt): AiResponse
    {
        ++$this->calls;

        return new AiResponse(array_shift($this->responses) ?? [], AiUsage::none());
    }

    public function calls(): int
    {
        return $this->calls;
    }
}
