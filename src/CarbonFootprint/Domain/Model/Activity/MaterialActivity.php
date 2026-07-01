<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class MaterialActivity extends Activity
{
    private MaterialId $materialId;
    private ItemCondition $condition;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        MaterialId $materialId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new MaterialActivityRegistered(
            activityId: $id,
            materialId: $materialId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        MaterialId $materialId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new MaterialActivityUpdated(
            activityId: $this->id,
            materialId: $materialId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new MaterialActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function materialId(): MaterialId
    {
        return $this->materialId;
    }

    public function condition(): ItemCondition
    {
        return $this->condition;
    }

    public function applyMaterialActivityRegistered(MaterialActivityRegistered $event): void
    {
        $this->materialId = $event->materialId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialActivityUpdated(MaterialActivityUpdated $event): void
    {
        $this->materialId = $event->materialId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
