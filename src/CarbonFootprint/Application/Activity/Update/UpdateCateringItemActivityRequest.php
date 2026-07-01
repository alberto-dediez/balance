<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class UpdateCateringItemActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public CateringItemId $cateringItemId,
        public ItemCondition $condition,
        public Quantity $quantity,
        public int $year,
    ) {}
}
