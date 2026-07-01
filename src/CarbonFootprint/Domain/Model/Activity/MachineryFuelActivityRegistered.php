<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MachineryFuelActivityRegistered extends DomainEvent
{
    protected static string $eventName = 'machineryFuelActivityRegistered';

    public function __construct(
        private ActivityId $activityId,
        private MachineryFuelId $machineryFuelId,
        private EmissionFactor $emissionFactor,
        private Emissions $emissions,
        private Quantity $quantity,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function machineryFuelId(): MachineryFuelId
    {
        return $this->machineryFuelId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function emissions(): Emissions
    {
        return $this->emissions;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->activityId;
    }

    public function payload(): array
    {
        return [
            'activityId' => (string) $this->activityId,
            'machineryFuelId' => (string) $this->machineryFuelId,
            'emissionFactorCo2eq' => $this->emissionFactor->co2eq(),
            'emissionFactorCo2' => $this->emissionFactor->co2(),
            'emissionFactorCh4' => $this->emissionFactor->ch4(),
            'emissionFactorN2o' => $this->emissionFactor->n2o(),
            'emissionFactorYear' => $this->emissionFactor->year(),
            'emissionFactorUnit' => $this->emissionFactor->unit()->value,
            'emissionFactorSource' => $this->emissionFactor->source(),
            'emissionsCo2eq' => $this->emissions->co2eq(),
            'emissionsCo2' => $this->emissions->co2(),
            'emissionsCh4' => $this->emissions->ch4(),
            'emissionsN2o' => $this->emissions->n2o(),
            'quantityValue' => $this->quantity->value(),
            'quantityUnit' => $this->quantity->unit()->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $quantityUnit = Unit::tryFrom($payload['quantityUnit']);
        assert(null !== $quantityUnit);

        $emissionFactorUnit = Unit::tryFrom($payload['emissionFactorUnit']);
        assert(null !== $emissionFactorUnit);

        return new self(
            activityId: new ActivityId($payload['activityId']),
            machineryFuelId: new MachineryFuelId($payload['machineryFuelId']),
            emissionFactor: new EmissionFactor(
                co2eq: $payload['emissionFactorCo2eq'],
                co2: $payload['emissionFactorCo2'],
                ch4: $payload['emissionFactorCh4'],
                n2o: $payload['emissionFactorN2o'],
                year: (int) $payload['emissionFactorYear'],
                unit: $emissionFactorUnit,
                source: $payload['emissionFactorSource'] ?? null,
            ),
            emissions: new Emissions(
                co2eq: $payload['emissionsCo2eq'] ?? '0',
                co2: $payload['emissionsCo2'] ?? '0',
                ch4: $payload['emissionsCh4'] ?? '0',
                n2o: $payload['emissionsN2o'] ?? '0',
            ),
            quantity: new Quantity((float) $payload['quantityValue'], $quantityUnit),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
