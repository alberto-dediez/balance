<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;

final class RefrigerantGasAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::RefrigerantGas;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        if (Magnitude::Mass !== $quantity->unit()->magnitude()) {
            return null;
        }

        return new RefrigerantGasAnswer(
            refrigerantGasId: new RefrigerantGasId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
        );
    }
}
