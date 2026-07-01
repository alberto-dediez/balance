<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CombustibleActivity extends Activity
{
    private CombustibleId $combustibleId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        CombustibleId $combustibleId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new CombustibleActivityRegistered(
            activityId: $id,
            combustibleId: $combustibleId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        CombustibleId $combustibleId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new CombustibleActivityUpdated(
            activityId: $this->id,
            combustibleId: $combustibleId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new CombustibleActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function combustibleId(): CombustibleId
    {
        return $this->combustibleId;
    }

    public function applyCombustibleActivityRegistered(CombustibleActivityRegistered $event): void
    {
        $this->combustibleId = $event->combustibleId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCombustibleActivityUpdated(CombustibleActivityUpdated $event): void
    {
        $this->combustibleId = $event->combustibleId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
