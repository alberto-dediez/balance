<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\Security\Domain\Model\Account\AccountId;

final class RefrigerantGasActivity extends Activity
{
    private RefrigerantGasId $refrigerantGasId;
    private ?string $source = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        RefrigerantGasId $refrigerantGasId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new RefrigerantGasActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            refrigerantGasId: $refrigerantGasId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));

        return $activity;
    }

    public function update(
        RefrigerantGasId $refrigerantGasId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): void {
        $this->recordApplyAndPublish(new RefrigerantGasActivityUpdated(
            activityId: $this->id,
            refrigerantGasId: $refrigerantGasId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));
    }

    public function remove(): void
    {
        $event = new RefrigerantGasActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function refrigerantGasId(): RefrigerantGasId
    {
        return $this->refrigerantGasId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function applyRefrigerantGasActivityRegistered(RefrigerantGasActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->refrigerantGasId = $event->refrigerantGasId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyRefrigerantGasActivityUpdated(RefrigerantGasActivityUpdated $event): void
    {
        $this->refrigerantGasId = $event->refrigerantGasId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->updatedAt = $event->occurredOn();
    }
}
