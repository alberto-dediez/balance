<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Application\CommandBus\Command;

class UpdateMachineryFuelActivityRequest implements Command
{
    public function __construct(
        public ActivityId $activityId,
        public MachineryFuelId $machineryFuelId,
        public Quantity $quantity,
        public int $year,
    ) {}
}
