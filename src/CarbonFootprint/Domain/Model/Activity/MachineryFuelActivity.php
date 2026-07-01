<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class MachineryFuelActivity extends Activity
{
    private MachineryFuelId $machineryFuelId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new MachineryFuelActivityRegistered(
            activityId: $id,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new MachineryFuelActivityUpdated(
            activityId: $this->id,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
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

    public function applyMachineryFuelActivityRegistered(MachineryFuelActivityRegistered $event): void
    {
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMachineryFuelActivityUpdated(MachineryFuelActivityUpdated $event): void
    {
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
