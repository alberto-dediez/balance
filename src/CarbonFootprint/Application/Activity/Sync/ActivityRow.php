<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Sync;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Quantity;

final class ActivityRow
{
    public function __construct(
        public ActivityType $activityType,
        public string $itemId,
        public ItemCondition $condition,
        public Quantity $quantity,
        public ?ActivityId $activityId = null,
        public ?string $secondaryItemId = null,
    ) {}
}
