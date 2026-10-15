<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class CapitalGoodAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly CapitalGoodId $capitalGoodId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
        public readonly ?int $acquisitionYear = null,
    ) {
        parent::__construct($quantity, $existingActivityId, $source);
    }

    public function type(): ActivityType
    {
        return ActivityType::CapitalGood;
    }
}
