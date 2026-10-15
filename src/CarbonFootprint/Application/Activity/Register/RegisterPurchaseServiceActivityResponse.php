<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Register;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterPurchaseServiceActivityResponse implements CommandResponse
{
    public function __construct(public ActivityId $activityId) {}
}
