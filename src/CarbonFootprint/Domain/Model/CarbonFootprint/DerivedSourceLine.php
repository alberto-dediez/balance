<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Unit;

final readonly class DerivedSourceLine
{
    public function __construct(
        public Source $source,
        public ActivityPlacement $origin,
        public ActivityType $activityType,
        public Emissions $emissions,
        public ActivityId $activityId,
        public float $quantity = 0.0,
        public Unit $unit = Unit::Kilogram,
        public string $factorCo2eq = '0',
        public Unit $factorUnit = Unit::Kilogram,
        public int $factorYear = 0,
        public ?string $factorSource = null,
    ) {}
}
