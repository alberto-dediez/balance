<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class IntegrationPosition
{
    public function __construct(
        private readonly string $source,
        private readonly string $aggregateId,
        private int $lastSequence,
        private \DateTimeImmutable $updatedAt,
    ) {}

    public function source(): string
    {
        return $this->source;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function lastSequence(): int
    {
        return $this->lastSequence;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function advance(int $sequence, \DateTimeImmutable $now): void
    {
        if ($sequence <= $this->lastSequence) {
            return;
        }

        $this->lastSequence = $sequence;
        $this->updatedAt = $now;
    }
}
