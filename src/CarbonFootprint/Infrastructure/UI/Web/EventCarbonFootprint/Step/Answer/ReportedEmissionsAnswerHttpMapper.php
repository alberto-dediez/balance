<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;

final class ReportedEmissionsAnswerHttpMapper extends AbstractSectionAnswerHttpMapper
{
    public function activityType(): ActivityType
    {
        return ActivityType::ReportedEmissions;
    }

    public function map(array $row): ?ActivityAnswer
    {
        $description = trim((string) ($row['description'] ?? ''));
        $quantity = $this->parseQuantity($row);

        if ('' === $description || null === $quantity) {
            return null;
        }

        if (!in_array($quantity->unit(), ReportedEmissionsActivity::allowedUnits(), true)) {
            return null;
        }

        return new ReportedEmissionsAnswer(
            description: $description,
            quantity: $quantity,
            existingActivityId: $this->parseActivityId($row),
        );
    }
}
