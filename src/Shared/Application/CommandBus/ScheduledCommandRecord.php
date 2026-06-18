<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class ScheduledCommandRecord
{
    public function __construct(
        private readonly string $commandId,
        private \DateTimeImmutable $lastRunAt,
    ) {}

    public function commandId(): string
    {
        return $this->commandId;
    }

    public function lastRunAt(): \DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function update(\DateTimeImmutable $now): void
    {
        $this->lastRunAt = $now;
    }
}
