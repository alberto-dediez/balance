<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Calculate;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class CalculateActivityEmissionsRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintId $eventCarbonFootprintId,
        public ActivityType $activityType,
        public string $itemId,
        public ?ItemCondition $condition,
        public Quantity $quantity,
        public ?string $secondaryItemId = null,
    ) {}
}
