<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;

final class WasteActivity extends Activity
{
    private WasteId $wasteId;
    private ?string $source = null;

    private function __construct(ActivityId $id)
    {
        $this->id = $id;
    }

    public static function register(
        ActivityId $id,
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): static {
        $activity = new static($id);
        $activity->recordApplyAndPublish(new WasteActivityRegistered(
            activityId: $id,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));

        return $activity;
    }

    public function update(
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): void {
        $this->recordApplyAndPublish(new WasteActivityUpdated(
            activityId: $this->id,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));
    }

    public function remove(): void
    {
        $event = new WasteActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function applyWasteActivityRegistered(WasteActivityRegistered $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteActivityUpdated(WasteActivityUpdated $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->updatedAt = $event->occurredOn();
    }
}
