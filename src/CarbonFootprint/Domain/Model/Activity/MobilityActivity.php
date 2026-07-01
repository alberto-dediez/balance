<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class MobilityActivity extends Activity
{
    private MobilityId $mobilityId;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        MobilityId $mobilityId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new MobilityActivityRegistered(
            activityId: $id,
            mobilityId: $mobilityId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        MobilityId $mobilityId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $this->recordApplyAndPublish(new MobilityActivityUpdated(
            activityId: $this->id,
            mobilityId: $mobilityId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new MobilityActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function mobilityId(): MobilityId
    {
        return $this->mobilityId;
    }

    public function applyMobilityActivityRegistered(MobilityActivityRegistered $event): void
    {
        $this->mobilityId = $event->mobilityId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMobilityActivityUpdated(MobilityActivityUpdated $event): void
    {
        $this->mobilityId = $event->mobilityId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }
}
