<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMarketerUpdated extends DomainEvent
{
    protected static string $eventName = 'electricityMarketerUpdated';

    public function __construct(
        private ElectricityMarketerId $electricityMarketerId,
        private string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMarketerId(): ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMarketerId();
    }

    public function payload(): array
    {
        return [
            'electricityMarketerId' => (string) $this->electricityMarketerId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMarketerUpdated(
            electricityMarketerId: new ElectricityMarketerId($payload['electricityMarketerId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
