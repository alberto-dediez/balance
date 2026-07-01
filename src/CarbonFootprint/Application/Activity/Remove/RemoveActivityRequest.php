<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Remove;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Application\CommandBus\Command;

class RemoveActivityRequest implements Command
{
    public function __construct(public ActivityId $activityId) {}
}
