<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class EconomicSectorActivity extends Activity
{
    private EconomicSectorId $economicSectorId;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        EconomicSectorId $economicSectorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new EconomicSectorActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            economicSectorId: $economicSectorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        EconomicSectorId $economicSectorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new EconomicSectorActivityUpdated(
            activityId: $this->id,
            economicSectorId: $economicSectorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new EconomicSectorActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function economicSectorId(): EconomicSectorId
    {
        return $this->economicSectorId;
    }

    public function applyEconomicSectorActivityRegistered(EconomicSectorActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->economicSectorId = $event->economicSectorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEconomicSectorActivityUpdated(EconomicSectorActivityUpdated $event): void
    {
        $this->economicSectorId = $event->economicSectorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
