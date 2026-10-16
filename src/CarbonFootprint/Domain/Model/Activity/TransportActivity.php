<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

final class TransportActivity extends Activity
{
    private TransportId $transportId;
    private ?string $source = null;
    private ?float $weightValue = null;
    private ?Unit $weightUnit = null;
    private ?float $distanceKm = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        TransportId $transportId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?Quantity $weight = null,
        ?float $distanceKm = null,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new TransportActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            transportId: $transportId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            weight: $weight,
            distanceKm: $distanceKm,
        ));

        return $activity;
    }

    public function update(
        TransportId $transportId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?Quantity $weight = null,
        ?float $distanceKm = null,
    ): void {
        $this->recordApplyAndPublish(new TransportActivityUpdated(
            activityId: $this->id,
            transportId: $transportId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            weight: $weight,
            distanceKm: $distanceKm,
        ));
    }

    public function remove(): void
    {
        $event = new TransportActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function transportId(): TransportId
    {
        return $this->transportId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function weight(): ?Quantity
    {
        if (null === $this->weightValue || null === $this->weightUnit) {
            return null;
        }

        return new Quantity($this->weightValue, $this->weightUnit);
    }

    public function distanceKm(): ?float
    {
        return $this->distanceKm;
    }

    public function applyTransportActivityRegistered(TransportActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->transportId = $event->transportId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->weightValue = $event->weight()?->value();
        $this->weightUnit = $event->weight()?->unit();
        $this->distanceKm = $event->distanceKm();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTransportActivityUpdated(TransportActivityUpdated $event): void
    {
        $this->transportId = $event->transportId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->weightValue = $event->weight()?->value();
        $this->weightUnit = $event->weight()?->unit();
        $this->distanceKm = $event->distanceKm();
        $this->updatedAt = $event->occurredOn();
    }
}
