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
    private EmissionFactor $wellToTankEmissionFactor;
    private Emissions $wellToTankEmissions;
    private ?string $locationBasedWellToTankEmissionFactorCo2eq = null;
    private ?string $locationBasedWellToTankEmissionFactorCo2 = null;
    private ?string $locationBasedWellToTankEmissionFactorCh4 = null;
    private ?string $locationBasedWellToTankEmissionFactorN2o = null;
    private ?string $locationBasedWellToTankEmissionFactorSource = null;
    private ?string $locationBasedWellToTankEmissionsCo2eq = null;
    private ?string $locationBasedWellToTankEmissionsCo2 = null;
    private ?string $locationBasedWellToTankEmissionsCh4 = null;
    private ?string $locationBasedWellToTankEmissionsN2o = null;

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
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
        ?EmissionFactor $locationBasedWellToTankEmissionFactor = null,
        ?Emissions $locationBasedWellToTankEmissions = null,
    ): static {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);
        self::assertLocationBasedResultIsComplete(
            $electricityMixId,
            $locationBasedEmissionFactor,
            $locationBasedEmissions,
            $locationBasedWellToTankEmissionFactor,
            $locationBasedWellToTankEmissions,
        );

        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

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
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
            electricityMixId: $electricityMixId,
            locationBasedEmissionFactor: $locationBasedEmissionFactor,
            locationBasedEmissions: $locationBasedEmissions,
            locationBasedWellToTankEmissionFactor: $locationBasedWellToTankEmissionFactor,
            locationBasedWellToTankEmissions: $locationBasedWellToTankEmissions,
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
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
        ?EmissionFactor $locationBasedWellToTankEmissionFactor = null,
        ?Emissions $locationBasedWellToTankEmissions = null,
    ): void {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);
        self::assertLocationBasedResultIsComplete(
            $electricityMixId,
            $locationBasedEmissionFactor,
            $locationBasedEmissions,
            $locationBasedWellToTankEmissionFactor,
            $locationBasedWellToTankEmissions,
        );

        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

        $this->recordApplyAndPublish(new ElectricityActivityUpdated(
            activityId: $this->id,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
            electricityMixId: $electricityMixId,
            locationBasedEmissionFactor: $locationBasedEmissionFactor,
            locationBasedEmissions: $locationBasedEmissions,
            locationBasedWellToTankEmissionFactor: $locationBasedWellToTankEmissionFactor,
            locationBasedWellToTankEmissions: $locationBasedWellToTankEmissions,
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
        ?EmissionFactor $locationBasedWellToTankEmissionFactor,
        ?Emissions $locationBasedWellToTankEmissions,
    ): void {
        $provided = count(array_filter(
            [$electricityMixId, $locationBasedEmissionFactor, $locationBasedEmissions, $locationBasedWellToTankEmissionFactor, $locationBasedWellToTankEmissions],
            static fn(?object $value): bool => null !== $value,
        ));

        if (0 !== $provided && 5 !== $provided) {
            throw new \InvalidArgumentException('Location-based result requires electricity mix, emission factor, emissions, well-to-tank emission factor and well-to-tank emissions.');
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

    public function wellToTankEmissionFactor(): EmissionFactor
    {
        return $this->wellToTankEmissionFactor;
    }

    public function wellToTankEmissions(): Emissions
    {
        return $this->wellToTankEmissions;
    }

    public function locationBasedWellToTankEmissionFactor(): ?EmissionFactor
    {
        if (null === $this->locationBasedWellToTankEmissionFactorCo2eq) {
            return null;
        }

        return new EmissionFactor(
            co2eq: $this->locationBasedWellToTankEmissionFactorCo2eq,
            co2: (string) $this->locationBasedWellToTankEmissionFactorCo2,
            ch4: (string) $this->locationBasedWellToTankEmissionFactorCh4,
            n2o: (string) $this->locationBasedWellToTankEmissionFactorN2o,
            year: $this->emissionFactor->year(),
            emissionFactorGroupId: $this->emissionFactor->emissionFactorGroupId(),
            unit: Unit::KWh,
            source: $this->locationBasedWellToTankEmissionFactorSource,
        );
    }

    public function locationBasedWellToTankEmissions(): ?Emissions
    {
        if (null === $this->locationBasedWellToTankEmissionsCo2eq) {
            return null;
        }

        return new Emissions(
            co2eq: $this->locationBasedWellToTankEmissionsCo2eq,
            co2: (string) $this->locationBasedWellToTankEmissionsCo2,
            ch4: (string) $this->locationBasedWellToTankEmissionsCh4,
            n2o: (string) $this->locationBasedWellToTankEmissionsN2o,
        );
    }

    private function applyLocationBasedResult(
        ?ElectricityMixId $electricityMixId,
        ?EmissionFactor $locationBasedEmissionFactor,
        ?Emissions $locationBasedEmissions,
        ?EmissionFactor $locationBasedWellToTankEmissionFactor,
        ?Emissions $locationBasedWellToTankEmissions,
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
        $this->locationBasedWellToTankEmissionFactorCo2eq = $locationBasedWellToTankEmissionFactor?->co2eq();
        $this->locationBasedWellToTankEmissionFactorCo2 = $locationBasedWellToTankEmissionFactor?->co2();
        $this->locationBasedWellToTankEmissionFactorCh4 = $locationBasedWellToTankEmissionFactor?->ch4();
        $this->locationBasedWellToTankEmissionFactorN2o = $locationBasedWellToTankEmissionFactor?->n2o();
        $this->locationBasedWellToTankEmissionFactorSource = $locationBasedWellToTankEmissionFactor?->source();
        $this->locationBasedWellToTankEmissionsCo2eq = $locationBasedWellToTankEmissions?->co2eq();
        $this->locationBasedWellToTankEmissionsCo2 = $locationBasedWellToTankEmissions?->co2();
        $this->locationBasedWellToTankEmissionsCh4 = $locationBasedWellToTankEmissions?->ch4();
        $this->locationBasedWellToTankEmissionsN2o = $locationBasedWellToTankEmissions?->n2o();
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
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->applyLocationBasedResult(
            $event->electricityMixId(),
            $event->locationBasedEmissionFactor(),
            $event->locationBasedEmissions(),
            $event->locationBasedWellToTankEmissionFactor(),
            $event->locationBasedWellToTankEmissions(),
        );
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
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->applyLocationBasedResult(
            $event->electricityMixId(),
            $event->locationBasedEmissionFactor(),
            $event->locationBasedEmissions(),
            $event->locationBasedWellToTankEmissionFactor(),
            $event->locationBasedWellToTankEmissions(),
        );
        $this->updatedAt = $event->occurredOn();
    }
}
