<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class MobilityAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly MobilityId $mobilityId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
    ) {
        parent::__construct($quantity, $existingActivityId);
    }

    public function type(): ActivityType
    {
        return ActivityType::Mobility;
    }
}
