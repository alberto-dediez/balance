<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;

final class WasteActivity extends Activity
{
    private WasteId $wasteId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new WasteActivityRegistered(
            activityId: $id,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new WasteActivityUpdated(
            activityId: $this->id,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new WasteActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function applyWasteActivityRegistered(WasteActivityRegistered $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteActivityUpdated(WasteActivityUpdated $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
