<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RoadTransportEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'roadTransportEmissionFactorRemoved';

    public function __construct(
        private RoadTransportId $roadTransportId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function roadTransportId(): RoadTransportId
    {
        return $this->roadTransportId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->roadTransportId();
    }

    public function payload(): array
    {
        return [
            'roadTransportId' => (string) $this->roadTransportId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new RoadTransportEmissionFactorRemoved(
            roadTransportId: new RoadTransportId($payload['roadTransportId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
