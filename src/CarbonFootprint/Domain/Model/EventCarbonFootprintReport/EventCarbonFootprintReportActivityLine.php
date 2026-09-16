<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Unit;

final class EventCarbonFootprintReportActivityLine
{
    public function __construct(
        public readonly string $itemName,
        public readonly ActivityType $activityType,
        public readonly Phase $phase,
        public readonly Source $source,
        public readonly float $quantity,
        public readonly Unit $unit,
        public readonly string $factorCo2eq,
        public readonly Unit $factorUnit,
        public readonly int $factorYear,
        public readonly ?string $factorSource,
        public readonly string $emissionsCo2eq,
    ) {}
}
