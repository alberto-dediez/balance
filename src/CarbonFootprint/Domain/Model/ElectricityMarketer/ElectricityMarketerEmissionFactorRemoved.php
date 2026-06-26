<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMarketerEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'electricityMarketerEmissionFactorRemoved';

    public function __construct(
        private ElectricityMarketerId $electricityMarketerId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMarketerId(): ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMarketerId();
    }

    public function payload(): array
    {
        return [
            'electricityMarketerId' => (string) $this->electricityMarketerId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMarketerEmissionFactorRemoved(
            electricityMarketerId: new ElectricityMarketerId($payload['electricityMarketerId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
