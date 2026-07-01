<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MobilityEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'mobilityEmissionFactorRemoved';

    public function __construct(
        private MobilityId $mobilityId,
        private int $year,
        private EmissionFactorGroupId $emissionFactorGroupId,
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

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
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
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
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
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
