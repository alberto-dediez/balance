<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class MerchandisingItemAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly MerchandisingItemId $merchandisingItemId,
        public readonly ItemCondition $condition,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
    ) {
        parent::__construct($quantity, $existingActivityId);
    }

    public function type(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }
}
