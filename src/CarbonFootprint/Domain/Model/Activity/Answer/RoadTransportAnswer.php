<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;

final class RoadTransportAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly RoadTransportId $roadTransportId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
    ) {
        parent::__construct($quantity, $existingActivityId, $source);
    }

    public function type(): ActivityType
    {
        return ActivityType::RoadTransport;
    }
}
