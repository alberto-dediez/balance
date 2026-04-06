<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointReasonUnlinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointReasonUnlinked';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        private readonly ReasonId $reasonId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId;
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'reasonId' => (string) $this->reasonId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointReasonUnlinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            reasonId: new ReasonId($payload['reasonId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
