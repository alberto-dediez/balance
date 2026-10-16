<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EconomicSectorRemoved extends DomainEvent
{
    protected static string $eventName = 'economicSectorRemoved';

    public function __construct(
        private EconomicSectorId $economicSectorId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function economicSectorId(): EconomicSectorId
    {
        return $this->economicSectorId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->economicSectorId();
    }

    public function payload(): array
    {
        return [
            'economicSectorId' => (string) $this->economicSectorId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EconomicSectorRemoved(
            economicSectorId: new EconomicSectorId($payload['economicSectorId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
