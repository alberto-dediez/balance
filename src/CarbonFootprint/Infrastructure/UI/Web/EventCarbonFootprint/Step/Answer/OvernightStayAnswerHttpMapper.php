<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\OvernightStayAnswer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;

final class OvernightStayAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::OvernightStay;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);

        if (null === $itemId) {
            return null;
        }

        $nights = $this->parsePositiveInt($row, 'nights');
        $people = $this->parsePositiveInt($row, 'people');

        if (null !== $nights && null !== $people) {
            $unit = Unit::tryFrom((string) ($row['unit'] ?? ''));

            if (null === $unit) {
                return null;
            }

            return new OvernightStayAnswer(
                overnightStayId: new OvernightStayId($itemId),
                quantity: new Quantity((float) ($nights * $people), $unit),
                existingActivityId: $this->parseActivityId($row),
                source: $this->parseSource($row),
                nights: $nights,
                people: $people,
            );
        }

        $quantity = $this->parseQuantity($row);

        if (null === $quantity) {
            return null;
        }

        return new OvernightStayAnswer(
            overnightStayId: new OvernightStayId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
        );
    }

    /** @param array<array-key, mixed> $row */
    private function parsePositiveInt(array $row, string $field): ?int
    {
        $value = trim((string) ($row[$field] ?? ''));

        if ('' === $value || !ctype_digit($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
