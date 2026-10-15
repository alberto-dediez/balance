<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class PurchaseServiceActivity extends Activity
{
    private PurchaseServiceId $purchaseServiceId;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        PurchaseServiceId $purchaseServiceId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new PurchaseServiceActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            purchaseServiceId: $purchaseServiceId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        PurchaseServiceId $purchaseServiceId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new PurchaseServiceActivityUpdated(
            activityId: $this->id,
            purchaseServiceId: $purchaseServiceId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new PurchaseServiceActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function applyPurchaseServiceActivityRegistered(PurchaseServiceActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->purchaseServiceId = $event->purchaseServiceId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPurchaseServiceActivityUpdated(PurchaseServiceActivityUpdated $event): void
    {
        $this->purchaseServiceId = $event->purchaseServiceId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
