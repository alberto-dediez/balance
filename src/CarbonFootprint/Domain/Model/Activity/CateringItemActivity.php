<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CateringItemActivity extends Activity
{
    private CateringItemId $cateringItemId;
    private ItemCondition $condition;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        CateringItemId $cateringItemId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new CateringItemActivityRegistered(
            activityId: $id,
            cateringItemId: $cateringItemId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        CateringItemId $cateringItemId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new CateringItemActivityUpdated(
            activityId: $this->id,
            cateringItemId: $cateringItemId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new CateringItemActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function cateringItemId(): CateringItemId
    {
        return $this->cateringItemId;
    }

    public function condition(): ItemCondition
    {
        return $this->condition;
    }

    public function applyCateringItemActivityRegistered(CateringItemActivityRegistered $event): void
    {
        $this->cateringItemId = $event->cateringItemId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCateringItemActivityUpdated(CateringItemActivityUpdated $event): void
    {
        $this->cateringItemId = $event->cateringItemId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
