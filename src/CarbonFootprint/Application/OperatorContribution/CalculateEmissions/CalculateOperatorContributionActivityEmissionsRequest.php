<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\CalculateEmissions;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CalculateOperatorContributionActivityEmissionsRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly ActivityType $activityType,
        public readonly string $itemId,
        public readonly ?ItemCondition $condition,
        public readonly Quantity $quantity,
        public readonly ?string $secondaryItemId = null,
    ) {}
}
