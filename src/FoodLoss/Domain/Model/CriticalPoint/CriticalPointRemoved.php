<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointRemoved extends DomainEvent
{
    protected static string $eventName = 'criticalPointRemoved';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId;
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointRemoved(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
