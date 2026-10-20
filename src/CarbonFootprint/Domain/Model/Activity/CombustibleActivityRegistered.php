<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CombustibleActivityRegistered extends DomainEvent
{
    protected static string $eventName = 'combustibleActivityRegistered';

    public function __construct(
        private ActivityId $activityId,
        private AccountId $accountId,
        private CombustibleId $combustibleId,
        private EmissionFactor $emissionFactor,
        private Emissions $emissions,
        private Quantity $quantity,
        private ?string $source,
        private EmissionFactor $wellToTankEmissionFactor,
        private Emissions $wellToTankEmissions,
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

    public function combustibleId(): CombustibleId
    {
        return $this->combustibleId;
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

    public function wellToTankEmissionFactor(): EmissionFactor
    {
        return $this->wellToTankEmissionFactor;
    }

    public function wellToTankEmissions(): Emissions
    {
        return $this->wellToTankEmissions;
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
            'combustibleId' => (string) $this->combustibleId,
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

        return new self(
            activityId: new ActivityId($payload['activityId']),
            accountId: new AccountId($payload['accountId']),
            combustibleId: new CombustibleId($payload['combustibleId']),
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
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
