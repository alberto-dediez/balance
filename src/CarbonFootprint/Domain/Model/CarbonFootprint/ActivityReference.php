<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;

final class ActivityReference
{
    public function __construct(
        public readonly ActivityId $activityId,
        public readonly ActivityPlacement $placement,
    ) {}
}
