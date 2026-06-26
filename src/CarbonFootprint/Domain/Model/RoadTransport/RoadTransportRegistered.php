<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RoadTransport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RoadTransportRegistered extends DomainEvent
{
    protected static string $eventName = 'roadTransportRegistered';

    public function __construct(
        private RoadTransportId $roadTransportId,
        private string $name,
        private Unit $unit,
        private Vehicle $vehicle,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function roadTransportId(): RoadTransportId
    {
        return $this->roadTransportId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function vehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->roadTransportId();
    }

    public function payload(): array
    {
        return [
            'roadTransportId' => (string) $this->roadTransportId,
            'name' => $this->name,
            'unit' => $this->unit->value,
            'vehicle' => $this->vehicle->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);
        $vehicle = Vehicle::from($payload['vehicle']);

        return new RoadTransportRegistered(
            roadTransportId: new RoadTransportId($payload['roadTransportId']),
            name: $payload['name'],
            unit: $unit,
            vehicle: $vehicle,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
