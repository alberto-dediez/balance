<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class IntegrationInboxMessage
{
    public function __construct(
        private readonly string $messageId,
        private readonly string $source,
        private readonly string $type,
        private readonly int $version,
        private readonly ?string $aggregateId,
        private readonly int $sequence,
        private readonly string $correlationId,
        private readonly ?string $causationId,
        private readonly int $hops,
        private readonly IntegrationInboxStatus $status,
        private readonly \DateTimeImmutable $receivedAt,
    ) {}

    public function messageId(): string
    {
        return $this->messageId;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function aggregateId(): ?string
    {
        return $this->aggregateId;
    }

    public function sequence(): int
    {
        return $this->sequence;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): ?string
    {
        return $this->causationId;
    }

    public function hops(): int
    {
        return $this->hops;
    }

    public function status(): IntegrationInboxStatus
    {
        return $this->status;
    }

    public function receivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }
}
