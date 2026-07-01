<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Remove;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveActivityResponse implements CommandResponse
{
    public function __construct(public ActivityId $activityId) {}
}
