<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMixEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'electricityMixEmissionFactorAdded';

    public function __construct(
        private ElectricityMixId $electricityMixId,
        private ElectricityMixEmissionFactorId $emissionFactorId,
        private EmissionFactor $emissionFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMixId(): ElectricityMixId
    {
        return $this->electricityMixId;
    }

    public function emissionFactorId(): ElectricityMixEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMixId();
    }

    public function payload(): array
    {
        return [
            'electricityMixId' => (string) $this->electricityMixId,
            'emissionFactorId' => (string) $this->emissionFactorId,
            'co2eq' => $this->emissionFactor->co2eq(),
            'co2' => $this->emissionFactor->co2(),
            'ch4' => $this->emissionFactor->ch4(),
            'n2o' => $this->emissionFactor->n2o(),
            'year' => $this->emissionFactor->year(),
            'unit' => $this->emissionFactor->unit()->value,
            'source' => $this->emissionFactor->source(),
            'emissionFactorGroupId' => (string) $this->emissionFactor->emissionFactorGroupId(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new ElectricityMixEmissionFactorAdded(
            electricityMixId: new ElectricityMixId($payload['electricityMixId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new ElectricityMixEmissionFactorId($payload['emissionFactorId'])
                : ElectricityMixEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
                co2eq: $payload['co2eq'],
                co2: $payload['co2'],
                ch4: $payload['ch4'],
                n2o: $payload['n2o'],
                year: $payload['year'],
                unit: $unit,
                source: $payload['source'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
