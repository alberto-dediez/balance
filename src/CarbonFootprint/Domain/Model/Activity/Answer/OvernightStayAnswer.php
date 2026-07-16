<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class OvernightStayAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly OvernightStayId $overnightStayId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
        public readonly ?int $nights = null,
        public readonly ?int $people = null,
    ) {
        parent::__construct($quantity, $existingActivityId, $source);
    }

    public function type(): ActivityType
    {
        return ActivityType::OvernightStay;
    }
}
