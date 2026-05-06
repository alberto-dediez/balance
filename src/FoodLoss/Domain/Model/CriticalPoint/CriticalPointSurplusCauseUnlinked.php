<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointSurplusCauseUnlinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointSurplusCauseUnlinked';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        private readonly SurplusCauseId $surplusCauseId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId;
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'surplusCauseId' => (string) $this->surplusCauseId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointSurplusCauseUnlinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
