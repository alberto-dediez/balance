<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class ElectricityActivity extends Activity
{
    private ?ElectricityMarketerId $electricityMarketerId = null;
    private ?GdOFactorId $gdOFactorId = null;
    private ?string $source = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        ?ElectricityMarketerId $electricityMarketerId,
        ?GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): static {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new ElectricityActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));

        return $activity;
    }

    public function update(
        ?ElectricityMarketerId $electricityMarketerId,
        ?GdOFactorId $gdOFactorId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
    ): void {
        self::assertHasMarketerOrGdOFactor($electricityMarketerId, $gdOFactorId);

        $this->recordApplyAndPublish(new ElectricityActivityUpdated(
            activityId: $this->id,
            electricityMarketerId: $electricityMarketerId,
            gdOFactorId: $gdOFactorId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
        ));
    }

    private static function assertHasMarketerOrGdOFactor(?ElectricityMarketerId $electricityMarketerId, ?GdOFactorId $gdOFactorId): void
    {
        if (null === $electricityMarketerId && null === $gdOFactorId) {
            throw new \InvalidArgumentException('Electricity activity requires an electricity marketer or a GdO factor.');
        }
    }

    public function remove(): void
    {
        $event = new ElectricityActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function electricityMarketerId(): ?ElectricityMarketerId
    {
        return $this->electricityMarketerId;
    }

    public function gdOFactorId(): ?GdOFactorId
    {
        return $this->gdOFactorId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function applyElectricityActivityRegistered(ElectricityActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyElectricityActivityUpdated(ElectricityActivityUpdated $event): void
    {
        $this->electricityMarketerId = $event->electricityMarketerId();
        $this->gdOFactorId = $event->gdOFactorId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->updatedAt = $event->occurredOn();
    }
}
