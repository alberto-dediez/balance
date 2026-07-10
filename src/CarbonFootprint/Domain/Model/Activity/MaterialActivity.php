<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class MaterialActivity extends Activity
{
    private MaterialId $materialId;
    private ItemCondition $condition;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        MaterialId $materialId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new MaterialActivityRegistered(
            activityId: $id,
            accountId: $accountId,
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
        $this->accountId = $event->accountId();
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
