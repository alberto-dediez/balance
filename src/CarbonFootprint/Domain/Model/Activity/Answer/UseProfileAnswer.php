<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;

final class UseProfileAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly UseProfileId $useProfileId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
    ) {
        parent::__construct($quantity, $existingActivityId);
    }

    public function type(): ActivityType
    {
        return ActivityType::UseProfile;
    }
}
