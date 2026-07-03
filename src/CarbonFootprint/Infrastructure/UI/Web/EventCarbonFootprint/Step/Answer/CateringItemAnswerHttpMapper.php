<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CateringItemAnswer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;

final class CateringItemAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::CateringItem;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        return new CateringItemAnswer(
            cateringItemId: new CateringItemId($itemId),
            condition: $this->parseCondition($row),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
        );
    }
}
