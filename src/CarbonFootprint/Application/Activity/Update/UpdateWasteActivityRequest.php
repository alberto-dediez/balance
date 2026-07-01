<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\Command;

class UpdateWasteActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public WasteId $wasteId,
        public Quantity $quantity,
        public int $year,
    ) {}
}
