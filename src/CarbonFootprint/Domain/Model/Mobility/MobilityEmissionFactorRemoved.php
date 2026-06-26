<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MobilityEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'mobilityEmissionFactorRemoved';

    public function __construct(
        private MobilityId $mobilityId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mobilityId();
    }

    public function payload(): array
    {
        return [
            'mobilityId' => (string) $this->mobilityId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MobilityEmissionFactorRemoved(
            mobilityId: new MobilityId($payload['mobilityId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
