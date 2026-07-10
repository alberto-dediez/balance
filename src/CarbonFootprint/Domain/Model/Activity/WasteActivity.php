<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\Account\AccountId;

final class WasteActivity extends Activity
{
    private WasteId $wasteId;
    private ?string $source = null;
    private ?float $containerCapacityValue = null;
    private ?Unit $containerCapacityUnit = null;
    private ?float $fillPercentage = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?Quantity $containerCapacity = null,
        ?float $fillPercentage = null,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new WasteActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            containerCapacity: $containerCapacity,
            fillPercentage: $fillPercentage,
        ));

        return $activity;
    }

    public function update(
        WasteId $wasteId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?Quantity $containerCapacity = null,
        ?float $fillPercentage = null,
    ): void {
        $this->recordApplyAndPublish(new WasteActivityUpdated(
            activityId: $this->id,
            wasteId: $wasteId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            containerCapacity: $containerCapacity,
            fillPercentage: $fillPercentage,
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

    public function containerCapacity(): ?Quantity
    {
        if (null === $this->containerCapacityValue || null === $this->containerCapacityUnit) {
            return null;
        }

        return new Quantity($this->containerCapacityValue, $this->containerCapacityUnit);
    }

    public function fillPercentage(): ?float
    {
        return $this->fillPercentage;
    }

    public function applyWasteActivityRegistered(WasteActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->containerCapacityValue = $event->containerCapacity()?->value();
        $this->containerCapacityUnit = $event->containerCapacity()?->unit();
        $this->fillPercentage = $event->fillPercentage();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyWasteActivityUpdated(WasteActivityUpdated $event): void
    {
        $this->wasteId = $event->wasteId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->containerCapacityValue = $event->containerCapacity()?->value();
        $this->containerCapacityUnit = $event->containerCapacity()?->unit();
        $this->fillPercentage = $event->fillPercentage();
        $this->updatedAt = $event->occurredOn();
    }
}
