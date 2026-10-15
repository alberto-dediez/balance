<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Security\Domain\Model\Account\AccountId;

final class UseProfileActivity extends Activity
{
    private UseProfileId $useProfileId;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        UseProfileId $useProfileId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new UseProfileActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            useProfileId: $useProfileId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        UseProfileId $useProfileId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new UseProfileActivityUpdated(
            activityId: $this->id,
            useProfileId: $useProfileId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new UseProfileActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function useProfileId(): UseProfileId
    {
        return $this->useProfileId;
    }

    public function applyUseProfileActivityRegistered(UseProfileActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->useProfileId = $event->useProfileId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyUseProfileActivityUpdated(UseProfileActivityUpdated $event): void
    {
        $this->useProfileId = $event->useProfileId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
