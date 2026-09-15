<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMixEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'electricityMixEmissionFactorRemoved';

    public function __construct(
        private ElectricityMixId $electricityMixId,
        private int $year,
        private EmissionFactorGroupId $emissionFactorGroupId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMixId(): ElectricityMixId
    {
        return $this->electricityMixId;
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
        return $this->electricityMixId();
    }

    public function payload(): array
    {
        return [
            'electricityMixId' => (string) $this->electricityMixId,
            'year' => $this->year,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ElectricityMixEmissionFactorRemoved(
            electricityMixId: new ElectricityMixId($payload['electricityMixId']),
            year: $payload['year'],
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
