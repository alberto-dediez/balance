<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MobilityRegistered extends DomainEvent
{
    protected static string $eventName = 'mobilityRegistered';

    public function __construct(
        private MobilityId $mobilityId,
        private string $name,
        private Unit $unit,
        private ?TravelMode $travelMode = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function travelMode(): ?TravelMode
    {
        return $this->travelMode;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mobilityId();
    }

    public function payload(): array
    {
        return [
            'mobilityId' => (string) $this->mobilityId,
            'name' => $this->name,
            'unit' => $this->unit->value,
            'travelMode' => $this->travelMode?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new MobilityRegistered(
            mobilityId: new MobilityId($payload['mobilityId']),
            name: $payload['name'],
            unit: $unit,
            travelMode: null !== ($payload['travelMode'] ?? null) ? TravelMode::from($payload['travelMode']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
