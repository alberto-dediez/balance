<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;

final class ReportedEmissionsActivity extends Activity
{
    private const int MAX_DESCRIPTION_LENGTH = 255;

    private string $description;

    public function __construct(ActivityId $id, ?AccountId $accountId = null)
    {
        $this->id = $id;
        $this->accountId = $accountId;
    }

    public static function register(
        ActivityId $id,
        AccountId $accountId,
        string $description,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): static {
        $description = self::ensureValidDescription($description);
        self::ensureValidUnit($quantity);

        $activity = new static($id, $accountId);
        $activity->recordApplyAndPublish(new ReportedEmissionsActivityRegistered(
            activityId: $id,
            accountId: $accountId,
            description: $description,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));

        return $activity;
    }

    public function update(
        string $description,
        EmissionFactor $emissionFactor,
        Emissions $emissions,
        Quantity $quantity,
    ): void {
        $description = self::ensureValidDescription($description);
        self::ensureValidUnit($quantity);

        $this->recordApplyAndPublish(new ReportedEmissionsActivityUpdated(
            activityId: $this->id,
            description: $description,
            emissionFactor: $emissionFactor,
            emissions: $emissions,
            quantity: $quantity,
        ));
    }

    public function remove(): void
    {
        $event = new ReportedEmissionsActivityRemoved(activityId: $this->id);
        $this->record($event);
        $this->publish($event);
    }

    public function description(): string
    {
        return $this->description;
    }

    /** @return list<Unit> */
    public static function allowedUnits(): array
    {
        return [Unit::Kilogram, Unit::Tonne];
    }

    public function applyReportedEmissionsActivityRegistered(ReportedEmissionsActivityRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->description = $event->description();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyReportedEmissionsActivityUpdated(ReportedEmissionsActivityUpdated $event): void
    {
        $this->description = $event->description();
        $this->emissionFactor = $event->emissionFactor();
        $this->emissions = $event->emissions();
        $this->quantity = $event->quantity();
        $this->updatedAt = $event->occurredOn();
    }

    public static function ensureValidDescription(string $description): string
    {
        $description = trim($description);

        if ('' === $description) {
            throw new \InvalidArgumentException('Reported emissions description cannot be empty.');
        }

        if (mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Reported emissions description cannot exceed %d characters.', self::MAX_DESCRIPTION_LENGTH));
        }

        return $description;
    }

    private static function ensureValidUnit(Quantity $quantity): void
    {
        if (!in_array($quantity->unit(), self::allowedUnits(), true)) {
            throw new IncompatibleUnitsException($quantity->unit(), Unit::Kilogram);
        }
    }
}
