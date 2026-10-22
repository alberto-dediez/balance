<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Emissions;

final readonly class DerivedSourceLine
{
    public function __construct(
        public Source $source,
        public ActivityPlacement $origin,
        public ActivityType $activityType,
        public Emissions $emissions,
        public ActivityId $activityId,
    ) {}
}
