<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;

final class EconomicSectorAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::EconomicSector;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        return new EconomicSectorAnswer(
            economicSectorId: new EconomicSectorId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
        );
    }
}
