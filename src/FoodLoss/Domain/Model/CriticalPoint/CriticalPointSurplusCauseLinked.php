<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointSurplusCauseLinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointSurplusCauseLinked';

    public function __construct(
        private readonly CriticalPointId $criticalPointId,
        private readonly SurplusCauseId $surplusCauseId,
        private readonly CriticalPointSurplusCauseLinkId $linkId,
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

    public function linkId(): CriticalPointSurplusCauseLinkId
    {
        return $this->linkId;
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
            'linkId' => (string) $this->linkId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointSurplusCauseLinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            linkId: isset($payload['linkId'])
                ? new CriticalPointSurplusCauseLinkId($payload['linkId'])
                : CriticalPointSurplusCauseLinkId::generate(),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
