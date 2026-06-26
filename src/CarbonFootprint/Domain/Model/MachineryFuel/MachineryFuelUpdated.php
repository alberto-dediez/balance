<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MachineryFuelUpdated extends DomainEvent
{
    protected static string $eventName = 'machineryFuelUpdated';

    public function __construct(
        private MachineryFuelId $machineryFuelId,
        private string $name,
        private Unit $unit,
        private MachineryType $machineryType,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function machineryFuelId(): MachineryFuelId
    {
        return $this->machineryFuelId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function machineryType(): MachineryType
    {
        return $this->machineryType;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->machineryFuelId();
    }

    public function payload(): array
    {
        return [
            'machineryFuelId' => (string) $this->machineryFuelId,
            'name' => $this->name,
            'unit' => $this->unit->value,
            'machineryType' => $this->machineryType->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MachineryFuelUpdated(
            machineryFuelId: new MachineryFuelId($payload['machineryFuelId']),
            name: $payload['name'],
            unit: Unit::from($payload['unit']),
            machineryType: MachineryType::from($payload['machineryType']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
