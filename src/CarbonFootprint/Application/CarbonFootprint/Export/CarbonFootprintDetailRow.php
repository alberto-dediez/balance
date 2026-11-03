<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Export;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Unit;

final readonly class CarbonFootprintDetailRow
{
    public function __construct(
        public CarbonFootprintDetailRowType $rowType,
        public CalculationStage $stage,
        public ?Source $source = null,
        public ?string $propertyName = null,
        public ?ActivityType $activityType = null,
        public ?string $itemName = null,
        public ?string $providerName = null,
        public bool $fromEmployees = false,
        public ?string $dataSource = null,
        public ?float $quantity = null,
        public ?Unit $unit = null,
        public ?ItemCondition $condition = null,
        public ?int $nights = null,
        public ?int $people = null,
        public ?float $weightTonnes = null,
        public ?float $distanceKm = null,
        public ?float $containerCapacityLitres = null,
        public ?float $fillPercentage = null,
        public ?int $acquisitionYear = null,
        public ?string $factorCo2eq = null,
        public ?Unit $factorUnit = null,
        public ?int $factorYear = null,
        public ?string $factorSource = null,
        public ?string $emissionsCo2eq = null,
        public ?string $emissionsCo2 = null,
        public ?string $emissionsCh4 = null,
        public ?string $emissionsN2o = null,
        public ?string $wellToTankCo2eq = null,
        public ?string $locationBasedCo2eq = null,
        public ?string $manualCo2eq = null,
        public ?ExclusionReason $exclusionReason = null,
        public ?string $exclusionComment = null,
        public ?\DateTimeImmutable $registeredAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
        public ?string $activityId = null,
        public ?float $materialityScorePercent = null,
        public ?int $materialityThresholdPercent = null,
        public ?MaterialityVerdict $materialityVerdict = null,
        public ?bool $materialityForcedByHighMagnitude = null,
        public ?string $materialityOverride = null,
    ) {}
}
