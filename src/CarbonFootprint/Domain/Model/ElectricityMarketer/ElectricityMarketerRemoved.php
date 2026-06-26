<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMarketerRemoved extends DomainEvent
{
    protected static string $eventName = 'electricityMarketerRemoved';

    public function __construct(
        private ElectricityMarketerId $electricityMarketerId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMarketerId(): ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMarketerId();
    }

    public function payload(): array
    {
        return [
            'electricityMarketerId' => (string) $this->electricityMarketerId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMarketerRemoved(
            electricityMarketerId: new ElectricityMarketerId($payload['electricityMarketerId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
