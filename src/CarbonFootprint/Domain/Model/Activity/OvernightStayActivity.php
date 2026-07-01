<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class OvernightStayActivity extends Activity
{
    private OvernightStayId $overnightStayId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        OvernightStayId $overnightStayId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new OvernightStayActivityRegistered(
            activityId: $id,
            overnightStayId: $overnightStayId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        OvernightStayId $overnightStayId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new OvernightStayActivityUpdated(
            activityId: $this->id,
            overnightStayId: $overnightStayId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new OvernightStayActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function overnightStayId(): OvernightStayId
    {
        return $this->overnightStayId;
    }

    public function applyOvernightStayActivityRegistered(OvernightStayActivityRegistered $event): void
    {
        $this->overnightStayId = $event->overnightStayId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOvernightStayActivityUpdated(OvernightStayActivityUpdated $event): void
    {
        $this->overnightStayId = $event->overnightStayId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
