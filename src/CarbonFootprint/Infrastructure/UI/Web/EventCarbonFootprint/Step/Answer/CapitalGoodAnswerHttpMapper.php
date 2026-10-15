<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CapitalGoodAnswer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;

final class CapitalGoodAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::CapitalGood;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $itemId = $this->parseItemId($row);
        $quantity = $this->parseQuantity($row);

        if (null === $itemId || null === $quantity) {
            return null;
        }

        return new CapitalGoodAnswer(
            capitalGoodId: new CapitalGoodId($itemId),
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
            source: $this->parseSource($row),
            acquisitionYear: $this->parseAcquisitionYear($row),
        );
    }

    /** @param array<array-key, mixed> $row */
    private function parseAcquisitionYear(array $row): ?int
    {
        $value = trim((string) ($row['acquisitionYear'] ?? ''));

        if ('' === $value || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
