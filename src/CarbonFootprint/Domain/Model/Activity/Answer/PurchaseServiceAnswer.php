<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class PurchaseServiceAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly PurchaseServiceId $purchaseServiceId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
    ) {
        parent::__construct($quantity, $existingActivityId);
    }

    public function type(): ActivityType
    {
        return ActivityType::PurchaseService;
    }
}
