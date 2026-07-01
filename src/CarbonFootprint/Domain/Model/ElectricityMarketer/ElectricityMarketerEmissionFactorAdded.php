<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityMarketerEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'electricityMarketerEmissionFactorAdded';

    public function __construct(
        private ElectricityMarketerId $electricityMarketerId,
        private ElectricityMarketerEmissionFactorId $emissionFactorId,
        private EmissionFactor $emissionFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function electricityMarketerId(): ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function emissionFactorId(): ElectricityMarketerEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->electricityMarketerId();
    }

    public function payload(): array
    {
        return [
            'electricityMarketerId' => (string) $this->electricityMarketerId,
            'emissionFactorId' => (string) $this->emissionFactorId,
            'co2eq' => $this->emissionFactor->co2eq(),
            'co2' => $this->emissionFactor->co2(),
            'ch4' => $this->emissionFactor->ch4(),
            'n2o' => $this->emissionFactor->n2o(),
            'year' => $this->emissionFactor->year(),
            'unit' => $this->emissionFactor->unit()->value,
            'source' => $this->emissionFactor->source(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new ElectricityMarketerEmissionFactorAdded(
            electricityMarketerId: new ElectricityMarketerId($payload['electricityMarketerId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new ElectricityMarketerEmissionFactorId($payload['emissionFactorId'])
                : ElectricityMarketerEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: EmissionFactorGroupId::default(),
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
