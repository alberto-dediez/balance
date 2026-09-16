<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;

final class RefrigerantGasRowViewFactory implements ActivityRowViewFactory
{
    public function activityType(): ActivityType
    {
        return ActivityType::RefrigerantGas;
    }

    public function create(Activity $activity): ActivityRowView
    {
        assert($activity instanceof RefrigerantGasActivity);

        return new ActivityRowView(
            activityId: (string) $activity->id(),
            itemId: (string) $activity->refrigerantGasId(),
            unit: $activity->quantity()->unit()->value,
            quantity: $activity->quantity()->value(),
            co2eq: $activity->emissions()->co2eq(),
            source: $activity->source(),
        );
    }
}
