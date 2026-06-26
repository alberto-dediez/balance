<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MachineryFuelEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'machineryFuelEmissionFactorRemoved';

    public function __construct(
        private MachineryFuelId $machineryFuelId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function machineryFuelId(): MachineryFuelId
    {
        return $this->machineryFuelId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->machineryFuelId();
    }

    public function payload(): array
    {
        return [
            'machineryFuelId' => (string) $this->machineryFuelId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MachineryFuelEmissionFactorRemoved(
            machineryFuelId: new MachineryFuelId($payload['machineryFuelId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
