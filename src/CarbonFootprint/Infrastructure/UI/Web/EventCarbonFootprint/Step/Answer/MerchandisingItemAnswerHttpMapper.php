<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MerchandisingItemAnswer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;

final class MerchandisingItemAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        return new MerchandisingItemAnswer(
            merchandisingItemId: new MerchandisingItemId($itemId),
            condition: $this->parseCondition($row),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
        );
    }
}
