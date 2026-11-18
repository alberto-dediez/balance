<?php

declare(strict_types=1);

namespace App\Assistant\Application\Usage\View;

use App\Assistant\Domain\Model\AssistantUsage;

final readonly class AssistantUsageTotals
{
    public function __construct(
        public int $messages = 0,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public string $costUsd = '0.000000',
    ) {}

    public function add(int $messages, int $inputTokens, int $outputTokens, string $costUsd): self
    {
        return new self(
            $this->messages + $messages,
            $this->inputTokens + $inputTokens,
            $this->outputTokens + $outputTokens,
            AssistantUsage::formatCost((float) $this->costUsd + (float) $costUsd),
        );
    }

    public function isEmpty(): bool
    {
        return 0 === $this->messages && 0 === $this->inputTokens && 0 === $this->outputTokens && (float) $this->costUsd <= 0.0;
    }
}
