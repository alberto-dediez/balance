<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\CalculateEmissions;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CalculateCompanyCarbonFootprintActivityEmissionsRequest
{
    public function __construct(
        public readonly CompanyCarbonFootprintId $companyCarbonFootprintId,
        public readonly ActivityType $activityType,
        public readonly string $itemId,
        public readonly ?ItemCondition $condition,
        public readonly Quantity $quantity,
        public readonly ?string $secondaryItemId = null,
    ) {}
}
