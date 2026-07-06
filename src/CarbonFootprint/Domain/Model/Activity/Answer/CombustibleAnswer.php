<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CombustibleAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly CombustibleId $combustibleId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
    ) {
        parent::__construct($quantity, $existingActivityId, $source);
    }

    public function type(): ActivityType
    {
        return ActivityType::Combustible;
    }
}
