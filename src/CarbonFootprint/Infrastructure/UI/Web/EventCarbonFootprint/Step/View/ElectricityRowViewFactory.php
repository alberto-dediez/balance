<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;

final class ElectricityRowViewFactory implements ActivityRowViewFactory
{
    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function create(Activity $activity): ActivityRowView
    {
        assert($activity instanceof ElectricityActivity);

        return new ActivityRowView(
            activityId: (string) $activity->id(),
            itemId: null !== $activity->electricityMarketerId() ? (string) $activity->electricityMarketerId() : '',
            unit: $activity->quantity()->unit()->value,
            quantity: $activity->quantity()->value(),
            co2eq: $activity->emissions()->co2eq(),
            itemId2: null !== $activity->gdOFactorId() ? (string) $activity->gdOFactorId() : null,
            source: $activity->source(),
        );
    }
}
