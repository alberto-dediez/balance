<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;

final class PurchaseServiceAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::PurchaseService;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        return new PurchaseServiceAnswer(
            purchaseServiceId: new PurchaseServiceId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
        );
    }
}
