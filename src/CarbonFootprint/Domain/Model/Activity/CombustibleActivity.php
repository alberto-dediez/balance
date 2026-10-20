<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class CombustibleActivity extends Activity
{
    private CombustibleId $combustibleId;
    private ?string $source = null;
    private EmissionFactor $wellToTankEmissionFactor;
    private Emissions $wellToTankEmissions;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        CombustibleId $combustibleId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
    ): static {
        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new CombustibleActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            combustibleId: $combustibleId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
        ));

        return $activity;
    }

    public function update(
        CombustibleId $combustibleId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?EmissionFactor $wellToTankEmissionFactor = null,
        ?Emissions $wellToTankEmissions = null,
    ): void {
        $wellToTankEmissionFactor ??= EmissionFactor::zeroFor($emissionFactor->year(), $emissionFactor->emissionFactorGroupId(), $emissionFactor->unit());
        $wellToTankEmissions ??= new Emissions();

        $this->recordApplyAndPublish(new CombustibleActivityUpdated(
            activityId: $this->id,
            combustibleId: $combustibleId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            wellToTankEmissionFactor: $wellToTankEmissionFactor,
            wellToTankEmissions: $wellToTankEmissions,
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

    public function source(): ?string
    {
        return $this->source;
    }

    public function wellToTankEmissionFactor(): EmissionFactor
    {
        return $this->wellToTankEmissionFactor;
    }

    public function wellToTankEmissions(): Emissions
    {
        return $this->wellToTankEmissions;
    }

    public function applyCombustibleActivityRegistered(CombustibleActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->combustibleId = $event->combustibleId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCombustibleActivityUpdated(CombustibleActivityUpdated $event): void
    {
        $this->combustibleId = $event->combustibleId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->wellToTankEmissionFactor = $event->wellToTankEmissionFactor();
        $this->wellToTankEmissions = $event->wellToTankEmissions();
        $this->updatedAt = $event->occurredOn();
    }
}
