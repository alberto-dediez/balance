<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class MachineryFuelActivity extends Activity
{
    private MachineryFuelId $machineryFuelId;
    private ?string $source = null;
    private EmissionFactor $wellToTankEmissionFactor;
    private Emissions $wellToTankEmissions;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
    ): static {
        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new MachineryFuelActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
        ));

        return $activity;
    }

    public function update(
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
    ): void {
        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

        $this->recordApplyAndPublish(new MachineryFuelActivityUpdated(
            activityId: $this->id,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
        ));
    }

    public function remove(): void
    {
        $event = new MachineryFuelActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function machineryFuelId(): MachineryFuelId
    {
        return $this->machineryFuelId;
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

    public function applyMachineryFuelActivityRegistered(MachineryFuelActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMachineryFuelActivityUpdated(MachineryFuelActivityUpdated $event): void
    {
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->updatedAt = $event->occurredOn();
    }
}
