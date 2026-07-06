<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;

final class RoadTransportActivity extends Activity
{
    private RoadTransportId $roadTransportId;
    private ?string $source = null;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        RoadTransportId $roadTransportId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new RoadTransportActivityRegistered(
            activityId: $id,
            roadTransportId: $roadTransportId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));

        return $activity;
    }

    public function update(
        RoadTransportId $roadTransportId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): void {
        $this->recordApplyAndPublish(new RoadTransportActivityUpdated(
            activityId: $this->id,
            roadTransportId: $roadTransportId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));
    }

    public function remove(): void
    {
        $event = new RoadTransportActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function roadTransportId(): RoadTransportId
    {
        return $this->roadTransportId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function applyRoadTransportActivityRegistered(RoadTransportActivityRegistered $event): void
    {
        $this->roadTransportId = $event->roadTransportId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyRoadTransportActivityUpdated(RoadTransportActivityUpdated $event): void
    {
        $this->roadTransportId = $event->roadTransportId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->updatedAt = $event->occurredOn();
    }
}
