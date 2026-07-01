<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class UpdateMobilityActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public MobilityId $mobilityId,
        public Quantity $quantity,
        public int $year,
    ) {}
}
