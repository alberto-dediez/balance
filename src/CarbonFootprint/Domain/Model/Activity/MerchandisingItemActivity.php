<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class MerchandisingItemActivity extends Activity
{
    private MerchandisingItemId $merchandisingItemId;
    private ItemCondition $condition;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        MerchandisingItemId $merchandisingItemId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new MerchandisingItemActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            merchandisingItemId: $merchandisingItemId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        MerchandisingItemId $merchandisingItemId,
        ItemCondition $condition,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new MerchandisingItemActivityUpdated(
            activityId: $this->id,
            merchandisingItemId: $merchandisingItemId,
            condition: $condition,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new MerchandisingItemActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function merchandisingItemId(): MerchandisingItemId
    {
        return $this->merchandisingItemId;
    }

    public function condition(): ItemCondition
    {
        return $this->condition;
    }

    public function applyMerchandisingItemActivityRegistered(MerchandisingItemActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->merchandisingItemId = $event->merchandisingItemId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMerchandisingItemActivityUpdated(MerchandisingItemActivityUpdated $event): void
    {
        $this->merchandisingItemId = $event->merchandisingItemId();
        $this->condition = $event->condition();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
