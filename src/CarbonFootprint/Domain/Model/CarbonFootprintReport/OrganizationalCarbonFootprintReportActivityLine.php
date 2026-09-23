<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;

final class OrganizationalCarbonFootprintReportActivityLine
{
    public function __construct(
        public readonly Scope $scope,
        public readonly Source $source,
        public readonly ?string $propertyName,
        public readonly string $itemName,
        public readonly ActivityType $activityType,
        public readonly float $quantity,
        public readonly Unit $unit,
        public readonly string $factorCo2eq,
        public readonly Unit $factorUnit,
        public readonly int $factorYear,
        public readonly ?string $factorSource,
        public readonly string $emissionsCo2eq,
        public readonly ?string $locationBasedEmissionsCo2eq = null,
    ) {}
}
