<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ExchangeRateSyncFailed extends DomainEvent
{
    protected static string $eventName = 'exchangeRateSyncFailed';

    public function __construct(
        private string $reason,
        private \DateTimeImmutable $attemptedAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function attemptedAt(): \DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    public function payload(): array
    {
        return [
            'reason' => $this->reason,
            'attemptedAt' => $this->attemptedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ExchangeRateSyncFailed(
            reason: $payload['reason'],
            attemptedAt: new \DateTimeImmutable($payload['attemptedAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
