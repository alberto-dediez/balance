<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Update;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateUseProfileActivityResponse implements CommandResponse
{
    public function __construct(public ActivityId $activityId) {}
}
