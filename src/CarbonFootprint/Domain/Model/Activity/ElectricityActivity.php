<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

final class ElectricityActivity extends Activity
{
    private ?ElectricityMarketerId $electricityMarketerId = null;
    private ?GdOFactorId $gdOFactorId = null;
    private ?string $source = null;
    private ?ElectricityMixId $electricityMixId = null;
    private ?string $locationBasedEmissionFactorCo2eq = null;
    private ?string $locationBasedEmissionFactorCo2 = null;
    private ?string $locationBasedEmissionFactorCh4 = null;
    private ?string $locationBasedEmissionFactorN2o = null;
    private ?string $locationBasedEmissionFactorSource = null;
    private ?string $locationBasedEmissionsCo2eq = null;
    private ?string $locationBasedEmissionsCo2 = null;
    private ?string $locationBasedEmissionsCh4 = null;
    private ?string $locationBasedEmissionsN2o = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        ?ElectricityMarketerId $electricityMarketerId,
        ?GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?ElectricityMixId $electricityMixId = null,
        ?EmissionFactor $locationBasedEmissionFactor = null,
        ?Emissions $locationBasedEmissions = null,
    ): static {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);
        self::assertLocationBasedResultIsComplete($electricityMixId, $locationBasedEmissionFactor, $locationBasedEmissions);

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new ElectricityActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            electricityMixId: $electricityMixId,
            locationBasedEmissionFactor: $locationBasedEmissionFactor,
            locationBasedEmissions: $locationBasedEmissions,
        ));

        return $activity;
    }

    public function update(
        ?ElectricityMarketerId $electricityMarketerId,
        ?GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?ElectricityMixId $electricityMixId = null,
        ?EmissionFactor $locationBasedEmissionFactor = null,
        ?Emissions $locationBasedEmissions = null,
    ): void {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);
        self::assertLocationBasedResultIsComplete($electricityMixId, $locationBasedEmissionFactor, $locationBasedEmissions);

        $this->recordApplyAndPublish(new ElectricityActivityUpdated(
            activityId: $this->id,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            electricityMixId: $electricityMixId,
            locationBasedEmissionFactor: $locationBasedEmissionFactor,
            locationBasedEmissions: $locationBasedEmissions,
        ));
    }

    private static function assertHasMarketerOrGdOFactor(?ElectricityMarketerId $electricityMarketerId, ?GdOFactorId $gdOFactorId): void
    {
        if (null === $electricityMarketerId && null === $gdOFactorId) {
            throw new \InvalidArgumentException('Electricity activity requires an electricity marketer or a GdO factor.');
        }
    }

    private static function assertLocationBasedResultIsComplete(
        ?ElectricityMixId $electricityMixId,
        ?EmissionFactor $locationBasedEmissionFactor,
        ?Emissions $locationBasedEmissions,
    ): void {
        $provided = count(array_filter(
            [$electricityMixId, $locationBasedEmissionFactor, $locationBasedEmissions],
            static fn(?object $value): bool => null !== $value,
        ));

        if (0 !== $provided && 3 !== $provided) {
            throw new \InvalidArgumentException('Location-based result requires electricity mix, emission factor and emissions.');
        }
    }

    public function remove(): void
    {
        $event = new ElectricityActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function electricityMarketerId(): ?ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function gdOFactorId(): ?GdOFactorId
    {
        return $this->gdOFactorId;
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
        if (null === $this->locationBasedEmissionFactorCo2eq) {
            return null;
        }

        return new EmissionFactor(
            co2eq: $this->locationBasedEmissionFactorCo2eq,
            co2: (string) $this->locationBasedEmissionFactorCo2,
            ch4: (string) $this->locationBasedEmissionFactorCh4,
            n2o: (string) $this->locationBasedEmissionFactorN2o,
            year: $this->emissionFactor->year(),
            emissionFactorGroupId: $this->emissionFactor->emissionFactorGroupId(),
            unit: Unit::KWh,
            source: $this->locationBasedEmissionFactorSource,
        );
    }

    public function locationBasedEmissions(): ?Emissions
    {
        if (null === $this->locationBasedEmissionsCo2eq) {
            return null;
        }

        return new Emissions(
            co2eq: $this->locationBasedEmissionsCo2eq,
            co2: (string) $this->locationBasedEmissionsCo2,
            ch4: (string) $this->locationBasedEmissionsCh4,
            n2o: (string) $this->locationBasedEmissionsN2o,
        );
    }

    private function applyLocationBasedResult(
        ?ElectricityMixId $electricityMixId,
        ?EmissionFactor $locationBasedEmissionFactor,
        ?Emissions $locationBasedEmissions,
    ): void {
        $this->electricityMixId = $electricityMixId;
        $this->locationBasedEmissionFactorCo2eq = $locationBasedEmissionFactor?->co2eq();
        $this->locationBasedEmissionFactorCo2 = $locationBasedEmissionFactor?->co2();
        $this->locationBasedEmissionFactorCh4 = $locationBasedEmissionFactor?->ch4();
        $this->locationBasedEmissionFactorN2o = $locationBasedEmissionFactor?->n2o();
        $this->locationBasedEmissionFactorSource = $locationBasedEmissionFactor?->source();
        $this->locationBasedEmissionsCo2eq = $locationBasedEmissions?->co2eq();
        $this->locationBasedEmissionsCo2 = $locationBasedEmissions?->co2();
        $this->locationBasedEmissionsCh4 = $locationBasedEmissions?->ch4();
        $this->locationBasedEmissionsN2o = $locationBasedEmissions?->n2o();
    }

    public function applyElectricityActivityRegistered(ElectricityActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->applyLocationBasedResult($event->electricityMixId(), $event->locationBasedEmissionFactor(), $event->locationBasedEmissions());
        $this->registeredAt = $event->occurredOn();
    }

    public function applyElectricityActivityUpdated(ElectricityActivityUpdated $event): void
    {
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->applyLocationBasedResult($event->electricityMixId(), $event->locationBasedEmissionFactor(), $event->locationBasedEmissions());
        $this->updatedAt = $event->occurredOn();
    }
}
