<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivity;

final class PurchaseServiceRowViewFactory implements ActivityRowViewFactory
{
    public function activityType(): ActivityType
    {
        return ActivityType::PurchaseService;
    }

    public function create(Activity $activity): ActivityRowView
    {
        assert($activity instanceof PurchaseServiceActivity);

        return new ActivityRowView(
            activityId: (string) $activity->id(),
            itemId: (string) $activity->purchaseServiceId(),
            unit: $activity->quantity()->unit()->value,
            quantity: $activity->quantity()->value(),
            co2eq: $activity->emissions()->co2eq(),
        );
    }
}
