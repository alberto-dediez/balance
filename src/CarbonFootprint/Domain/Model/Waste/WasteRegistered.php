<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Waste;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class WasteRegistered extends DomainEvent
{
    protected static string $eventName = 'wasteRegistered';

    public function __construct(
        private WasteId $wasteId,
        private string $name,
        private Unit $unit,
        private float $density,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function density(): float
    {
        return $this->density;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteId();
    }

    public function payload(): array
    {
        return [
            'wasteId' => (string) $this->wasteId,
            'name' => $this->name,
            'unit' => $this->unit->value,
            'density' => $this->density,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new WasteRegistered(
            wasteId: new WasteId($payload['wasteId']),
            name: $payload['name'],
            unit: $unit,
            density: (float) $payload['density'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
