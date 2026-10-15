<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Security\Domain\Model\Account\AccountId;

final class CapitalGoodActivity extends Activity
{
    private const int MIN_ACQUISITION_YEAR = 1900;
    private const int MAX_ACQUISITION_YEAR = 2200;

    private CapitalGoodId $capitalGoodId;
    private ?int $acquisitionYear = null;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        CapitalGoodId $capitalGoodId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?int $acquisitionYear = null,
    ): static {
        self::guardAcquisitionYear($acquisitionYear);

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new CapitalGoodActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            capitalGoodId: $capitalGoodId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            acquisitionYear: $acquisitionYear,
        ));

        return $activity;
    }

    public function update(
        CapitalGoodId $capitalGoodId,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
        ?int $acquisitionYear = null,
    ): void {
        self::guardAcquisitionYear($acquisitionYear);

        $this->recordApplyAndPublish(new CapitalGoodActivityUpdated(
            activityId: $this->id,
            capitalGoodId: $capitalGoodId,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
            acquisitionYear: $acquisitionYear,
        ));
    }

    public function remove(): void
    {
        $event = new CapitalGoodActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return $this->capitalGoodId;
    }

    public function acquisitionYear(): ?int
    {
        return $this->acquisitionYear;
    }

    private static function guardAcquisitionYear(?int $acquisitionYear): void
    {
        if (null === $acquisitionYear) {
            return;
        }

        if ($acquisitionYear < self::MIN_ACQUISITION_YEAR || $acquisitionYear > self::MAX_ACQUISITION_YEAR) {
            throw new InvalidAcquisitionYearException($acquisitionYear, self::MIN_ACQUISITION_YEAR, self::MAX_ACQUISITION_YEAR);
        }
    }

    public function applyCapitalGoodActivityRegistered(CapitalGoodActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->capitalGoodId = $event->capitalGoodId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->acquisitionYear = $event->acquisitionYear();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCapitalGoodActivityUpdated(CapitalGoodActivityUpdated $event): void
    {
        $this->capitalGoodId = $event->capitalGoodId();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->acquisitionYear = $event->acquisitionYear();
        $this->updatedAt = $event->occurredOn();
    }
}
