<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Integration;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class IntegrationOutboxMessage
{
    private const int MAX_ERROR_LENGTH = 1000;

    public function __construct(
        private ?int $sequence,
        private readonly string $messageId,
        private readonly string $type,
        private readonly int $version,
        private readonly ?string $aggregateId,
        private readonly ?string $accountRef,
        private readonly string $correlationId,
        private readonly ?string $causationId,
        private readonly int $hops,
        private readonly \DateTimeImmutable $occurredOn,
        private readonly string $payload,
        private ?\DateTimeImmutable $sentAt = null,
        private int $attempts = 0,
        private ?string $lastError = null,
    ) {}

    public function sequence(): ?int
    {
        return $this->sequence;
    }

    public function messageId(): string
    {
        return $this->messageId;
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

    public function accountRef(): ?string
    {
        return $this->accountRef;
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

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function routingKey(): string
    {
        return sprintf('%s.v%d', $this->type, $this->version);
    }

    public function isSent(): bool
    {
        return null !== $this->sentAt;
    }

    public function markSent(\DateTimeImmutable $now): void
    {
        $this->sentAt = $now;
        $this->lastError = null;
    }

    public function markFailedAttempt(string $error): void
    {
        ++$this->attempts;
        $this->lastError = mb_substr($error, 0, self::MAX_ERROR_LENGTH);
    }
}
