<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Quantity;

abstract class ActivityAnswer
{
    public function __construct(
        public readonly Quantity $quantity,
        public readonly ?ActivityId $existingActivityId = null,
        public readonly ?string $source = null,
    ) {}

    abstract public function type(): ActivityType;
}
