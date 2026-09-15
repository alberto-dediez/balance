<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMixRemoved extends DomainEvent
{
    protected static string $eventName = 'electricityMixRemoved';

    public function __construct(
        private ElectricityMixId $electricityMixId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMixId(): ElectricityMixId
    {
        return $this->electricityMixId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMixId();
    }

    public function payload(): array
    {
        return [
            'electricityMixId' => (string) $this->electricityMixId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMixRemoved(
            electricityMixId: new ElectricityMixId($payload['electricityMixId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
