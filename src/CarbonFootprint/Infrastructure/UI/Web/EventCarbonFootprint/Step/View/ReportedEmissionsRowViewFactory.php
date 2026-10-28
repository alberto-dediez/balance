<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;

final class ReportedEmissionsRowViewFactory implements ActivityRowViewFactory
{
    public function activityType(): ActivityType
    {
        return ActivityType::ReportedEmissions;
    }

    public function create(Activity $activity): ActivityRowView
    {
        assert($activity instanceof ReportedEmissionsActivity);

        return new ActivityRowView(
            activityId: (string) $activity->id(),
            itemId: '',
            unit: $activity->quantity()->unit()->value,
            quantity: $activity->quantity()->value(),
            co2eq: $activity->emissions()->co2eq(),
            description: $activity->description(),
        );
    }

    public function createFromAnswer(ReportedEmissionsAnswer $answer): ActivityRowView
    {
        return new ActivityRowView(
            activityId: (string) $answer->existingActivityId,
            itemId: '',
            unit: $answer->quantity->unit()->value,
            quantity: $answer->quantity->value(),
            co2eq: '',
            description: $answer->description,
        );
    }
}
