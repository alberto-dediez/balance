<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class MachineryFuelActivity extends Activity
{
    private MachineryFuelId $machineryFuelId;
    private ?string $source = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new MachineryFuelActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));

        return $activity;
    }

    public function update(
        MachineryFuelId $machineryFuelId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): void {
        $this->recordApplyAndPublish(new MachineryFuelActivityUpdated(
            activityId: $this->id,
            machineryFuelId: $machineryFuelId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));
    }

    public function remove(): void
    {
        $event = new MachineryFuelActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function machineryFuelId(): MachineryFuelId
    {
        return $this->machineryFuelId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function applyMachineryFuelActivityRegistered(MachineryFuelActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMachineryFuelActivityUpdated(MachineryFuelActivityUpdated $event): void
    {
        $this->machineryFuelId = $event->machineryFuelId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->updatedAt = $event->occurredOn();
    }
}
