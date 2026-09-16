<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\CalculateEmissions;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CalculatePropertyCarbonFootprintActivityEmissionsRequest
{
    public function __construct(
        public readonly PropertyCarbonFootprintId $propertyCarbonFootprintId,
        public readonly ActivityType $activityType,
        public readonly string $itemId,
        public readonly ?ItemCondition $condition,
        public readonly Quantity $quantity,
        public readonly ?string $secondaryItemId = null,
    ) {}
}
