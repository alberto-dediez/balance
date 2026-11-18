<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

interface CurrentAssistantExecution
{
    public function begin(string $executionId): void;

    public function end(): void;

    public function executionId(): ?string;
}
