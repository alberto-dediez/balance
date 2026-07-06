<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;

final class ElectricityAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $secondaryItemId = $this->parseSecondaryItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $secondaryItemId || null === $quantity) {
            return null;
        }

        return new ElectricityAnswer(
            electricityMarketerId: new ElectricityMarketerId($itemId),
            gdOFactorId: new GdOFactorId($secondaryItemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
        );
    }
}
