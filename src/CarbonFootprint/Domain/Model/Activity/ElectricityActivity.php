<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class ElectricityActivity extends Activity
{
    private ElectricityMarketerId $electricityMarketerId;
    private GdOFactorId $gdOFactorId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        ElectricityMarketerId $electricityMarketerId,
        GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new ElectricityActivityRegistered(
            activityId: $id,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        ElectricityMarketerId $electricityMarketerId,
        GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new ElectricityActivityUpdated(
            activityId: $this->id,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new ElectricityActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function electricityMarketerId(): ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function gdOFactorId(): GdOFactorId
    {
        return $this->gdOFactorId;
    }

    public function applyElectricityActivityRegistered(ElectricityActivityRegistered $event): void
    {
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyElectricityActivityUpdated(ElectricityActivityUpdated $event): void
    {
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
