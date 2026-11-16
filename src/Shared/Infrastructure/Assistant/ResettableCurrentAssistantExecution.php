<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Assistant;

use App\Shared\Application\Assistant\CurrentAssistantExecution;
use Symfony\Contracts\Service\ResetInterface;

final class ResettableCurrentAssistantExecution implements CurrentAssistantExecution, ResetInterface
{
    private ?string $executionId = null;

    public function begin(string $executionId): void
    {
        $this->executionId = $executionId;
    }

    public function end(): void
    {
        $this->executionId = null;
    }

    public function executionId(): ?string
    {
        return $this->executionId;
    }

    public function reset(): void
    {
        $this->end();
    }
}
