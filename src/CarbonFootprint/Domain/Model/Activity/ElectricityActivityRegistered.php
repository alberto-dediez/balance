<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ElectricityActivityRegistered extends DomainEvent
{
    protected static string $eventName = 'electricityActivityRegistered';

    public function __construct(
        private ActivityId $activityId,
        private AccountId $accountId,
        private ?ElectricityMarketerId $electricityMarketerId,
        private ?GdOFactorId $gdOFactorId,
        private EmissionFactor $emissionFactor,
        private Emissions $emissions,
        private Quantity $quantity,
        private ?string $source,
        private EmissionFactor $wellToTankEmissionFactor,
        private Emissions $wellToTankEmissions,
        private ?ElectricityMixId $electricityMixId = null,
        private ?EmissionFactor $locationBasedEmissionFactor = null,
        private ?Emissions $locationBasedEmissions = null,
        private ?EmissionFactor $locationBasedWellToTankEmissionFactor = null,
        private ?Emissions $locationBasedWellToTankEmissions = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function electricityMarketerId(): ?ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function gdOFactorId(): ?GdOFactorId
    {
        return $this->gdOFactorId;
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

    public function source(): ?string
    {
        return $this->source;
    }

    public function electricityMixId(): ?ElectricityMixId
    {
        return $this->electricityMixId;
    }

    public function locationBasedEmissionFactor(): ?EmissionFactor
    {
        return $this->locationBasedEmissionFactor;
    }

    public function wellToTankEmissionFactor(): EmissionFactor
    {
        return $this->wellToTankEmissionFactor;
    }

    public function wellToTankEmissions(): Emissions
    {
        return $this->wellToTankEmissions;
    }

    public function locationBasedEmissions(): ?Emissions
    {
        return $this->locationBasedEmissions;
    }

    public function locationBasedWellToTankEmissionFactor(): ?EmissionFactor
    {
        return $this->locationBasedWellToTankEmissionFactor;
    }

    public function locationBasedWellToTankEmissions(): ?Emissions
    {
        return $this->locationBasedWellToTankEmissions;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->activityId;
    }

    public function payload(): array
    {
        return [
            'activityId' => (string) $this->activityId,
            'accountId' => (string) $this->accountId,
            'electricityMarketerId' => null !== $this->electricityMarketerId ? (string) $this->electricityMarketerId : null,
            'gdOFactorId' => null !== $this->gdOFactorId ? (string) $this->gdOFactorId : null,
            'emissionFactorCo2eq' => $this->emissionFactor->co2eq(),
            'emissionFactorCo2' => $this->emissionFactor->co2(),
            'emissionFactorCh4' => $this->emissionFactor->ch4(),
            'emissionFactorN2o' => $this->emissionFactor->n2o(),
            'emissionFactorYear' => $this->emissionFactor->year(),
            'emissionFactorUnit' => $this->emissionFactor->unit()->value,
            'emissionFactorSource' => $this->emissionFactor->source(),
            'emissionFactorGroupId' => (string) $this->emissionFactor->emissionFactorGroupId(),
            'emissionsCo2eq' => $this->emissions->co2eq(),
            'emissionsCo2' => $this->emissions->co2(),
            'emissionsCh4' => $this->emissions->ch4(),
            'emissionsN2o' => $this->emissions->n2o(),
            'quantityValue' => $this->quantity->value(),
            'quantityUnit' => $this->quantity->unit()->value,
            'source' => $this->source,
            'wellToTankEmissionFactorCo2eq' => $this->wellToTankEmissionFactor->co2eq(),
            'wellToTankEmissionFactorCo2' => $this->wellToTankEmissionFactor->co2(),
            'wellToTankEmissionFactorCh4' => $this->wellToTankEmissionFactor->ch4(),
            'wellToTankEmissionFactorN2o' => $this->wellToTankEmissionFactor->n2o(),
            'wellToTankEmissionFactorSource' => $this->wellToTankEmissionFactor->source(),
            'wellToTankEmissionsCo2eq' => $this->wellToTankEmissions->co2eq(),
            'wellToTankEmissionsCo2' => $this->wellToTankEmissions->co2(),
            'wellToTankEmissionsCh4' => $this->wellToTankEmissions->ch4(),
            'wellToTankEmissionsN2o' => $this->wellToTankEmissions->n2o(),
            'electricityMixId' => null !== $this->electricityMixId ? (string) $this->electricityMixId : null,
            'locationBasedEmissionFactorCo2eq' => $this->locationBasedEmissionFactor?->co2eq(),
            'locationBasedEmissionFactorCo2' => $this->locationBasedEmissionFactor?->co2(),
            'locationBasedEmissionFactorCh4' => $this->locationBasedEmissionFactor?->ch4(),
            'locationBasedEmissionFactorN2o' => $this->locationBasedEmissionFactor?->n2o(),
            'locationBasedEmissionFactorSource' => $this->locationBasedEmissionFactor?->source(),
            'locationBasedEmissionsCo2eq' => $this->locationBasedEmissions?->co2eq(),
            'locationBasedEmissionsCo2' => $this->locationBasedEmissions?->co2(),
            'locationBasedEmissionsCh4' => $this->locationBasedEmissions?->ch4(),
            'locationBasedEmissionsN2o' => $this->locationBasedEmissions?->n2o(),
            'locationBasedWellToTankEmissionFactorCo2eq' => $this->locationBasedWellToTankEmissionFactor?->co2eq(),
            'locationBasedWellToTankEmissionFactorCo2' => $this->locationBasedWellToTankEmissionFactor?->co2(),
            'locationBasedWellToTankEmissionFactorCh4' => $this->locationBasedWellToTankEmissionFactor?->ch4(),
            'locationBasedWellToTankEmissionFactorN2o' => $this->locationBasedWellToTankEmissionFactor?->n2o(),
            'locationBasedWellToTankEmissionFactorSource' => $this->locationBasedWellToTankEmissionFactor?->source(),
            'locationBasedWellToTankEmissionsCo2eq' => $this->locationBasedWellToTankEmissions?->co2eq(),
            'locationBasedWellToTankEmissionsCo2' => $this->locationBasedWellToTankEmissions?->co2(),
            'locationBasedWellToTankEmissionsCh4' => $this->locationBasedWellToTankEmissions?->ch4(),
            'locationBasedWellToTankEmissionsN2o' => $this->locationBasedWellToTankEmissions?->n2o(),
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

        $emissionFactorYear = (int) $payload['emissionFactorYear'];
        $emissionFactorGroupId = new EmissionFactorGroupId($payload['emissionFactorGroupId']);
        $hasLocationBasedResult = null !== ($payload['locationBasedEmissionFactorCo2eq'] ?? null);

        $wellToTankEmissionFactor = isset($payload['wellToTankEmissionFactorCo2eq'])
            ? new EmissionFactor(
                co2eq: $payload['wellToTankEmissionFactorCo2eq'],
                co2: $payload['wellToTankEmissionFactorCo2'],
                ch4: $payload['wellToTankEmissionFactorCh4'],
                n2o: $payload['wellToTankEmissionFactorN2o'],
                year: $emissionFactorYear,
                emissionFactorGroupId: $emissionFactorGroupId,
                unit: $emissionFactorUnit,
                source: $payload['wellToTankEmissionFactorSource'] ?? null,
            )
            : EmissionFactor::zeroFor($emissionFactorYear, $emissionFactorGroupId, $emissionFactorUnit);

        $locationBasedWellToTankEmissionFactor = $hasLocationBasedResult
            ? new EmissionFactor(
                co2eq: $payload['locationBasedWellToTankEmissionFactorCo2eq'] ?? '0',
                co2: $payload['locationBasedWellToTankEmissionFactorCo2'] ?? '0',
                ch4: $payload['locationBasedWellToTankEmissionFactorCh4'] ?? '0',
                n2o: $payload['locationBasedWellToTankEmissionFactorN2o'] ?? '0',
                year: $emissionFactorYear,
                emissionFactorGroupId: $emissionFactorGroupId,
                unit: Unit::KWh,
                source: $payload['locationBasedWellToTankEmissionFactorSource'] ?? null,
            )
            : null;

        $locationBasedWellToTankEmissions = $hasLocationBasedResult
            ? new Emissions(
                co2eq: $payload['locationBasedWellToTankEmissionsCo2eq'] ?? '0',
                co2: $payload['locationBasedWellToTankEmissionsCo2'] ?? '0',
                ch4: $payload['locationBasedWellToTankEmissionsCh4'] ?? '0',
                n2o: $payload['locationBasedWellToTankEmissionsN2o'] ?? '0',
            )
            : null;

        return new self(
            activityId: new ActivityId($payload['activityId']),
            accountId: new AccountId($payload['accountId']),
            electricityMarketerId: null !== ($payload['electricityMarketerId'] ?? null) ? new ElectricityMarketerId($payload['electricityMarketerId']) : null,
            gdOFactorId: null !== ($payload['gdOFactorId'] ?? null) ? new GdOFactorId($payload['gdOFactorId']) : null,
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: $emissionFactorGroupId,
                co2eq: $payload['emissionFactorCo2eq'],
                co2: $payload['emissionFactorCo2'],
                ch4: $payload['emissionFactorCh4'],
                n2o: $payload['emissionFactorN2o'],
                year: $emissionFactorYear,
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
            source: $payload['source'] ?? null,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: new Emissions(
                co2eq: $payload['wellToTankEmissionsCo2eq'] ?? '0',
                co2: $payload['wellToTankEmissionsCo2'] ?? '0',
                ch4: $payload['wellToTankEmissionsCh4'] ?? '0',
                n2o: $payload['wellToTankEmissionsN2o'] ?? '0',
            ),
            electricityMixId: null !== ($payload['electricityMixId'] ?? null) ? new ElectricityMixId($payload['electricityMixId']) : null,
            locationBasedEmissionFactor: $hasLocationBasedResult ? new EmissionFactor(
                emissionFactorGroupId: $emissionFactorGroupId,
                co2eq: $payload['locationBasedEmissionFactorCo2eq'],
                co2: $payload['locationBasedEmissionFactorCo2'] ?? '0',
                ch4: $payload['locationBasedEmissionFactorCh4'] ?? '0',
                n2o: $payload['locationBasedEmissionFactorN2o'] ?? '0',
                year: $emissionFactorYear,
                unit: Unit::KWh,
                source: $payload['locationBasedEmissionFactorSource'] ?? null,
            ) : null,
            locationBasedEmissions: $hasLocationBasedResult ? new Emissions(
                co2eq: $payload['locationBasedEmissionsCo2eq'] ?? '0',
                co2: $payload['locationBasedEmissionsCo2'] ?? '0',
                ch4: $payload['locationBasedEmissionsCh4'] ?? '0',
                n2o: $payload['locationBasedEmissionsN2o'] ?? '0',
            ) : null,
            locationBasedWellToTankEmissionFactor: $locationBasedWellToTankEmissionFactor,
            locationBasedWellToTankEmissions: $locationBasedWellToTankEmissions,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
