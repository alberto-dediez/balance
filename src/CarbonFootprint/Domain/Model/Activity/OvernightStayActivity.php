<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class OvernightStayActivity extends Activity
{
    private OvernightStayId $overnightStayId;
    private ?string $source = null;
    private ?int $nights = null;
    private ?int $people = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        OvernightStayId $overnightStayId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?int $nights = null,
        ?int $people = null,
    ): static {
        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new OvernightStayActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            overnightStayId: $overnightStayId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            nights: $nights,
            people: $people,
        ));

        return $activity;
    }

    public function update(
        OvernightStayId $overnightStayId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?string $source = null,
        ?int $nights = null,
        ?int $people = null,
    ): void {
        $this->recordApplyAndPublish(new OvernightStayActivityUpdated(
            activityId: $this->id,
            overnightStayId: $overnightStayId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            source: $source,
            nights: $nights,
            people: $people,
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

    public function source(): ?string
    {
        return $this->source;
    }

    public function nights(): ?int
    {
        return $this->nights;
    }

    public function people(): ?int
    {
        return $this->people;
    }

    public function applyOvernightStayActivityRegistered(OvernightStayActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->overnightStayId = $event->overnightStayId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->nights = $event->nights();
        $this->people = $event->people();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOvernightStayActivityUpdated(OvernightStayActivityUpdated $event): void
    {
        $this->overnightStayId = $event->overnightStayId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->source = $event->source();
        $this->nights = $event->nights();
        $this->people = $event->people();
        $this->updatedAt = $event->occurredOn();
    }
}
