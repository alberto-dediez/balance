<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\DailyWaste;

use App\Waste\Domain\Model\DailyWaste\DailyWaste as ModelDailyWaste;

class DailyWasteResponseGenerator
{
    public function generate(ModelDailyWaste $model): DailyWasteResponse
    {
        $propertyId = $model->propertyId();
        assert(null !== $propertyId);
        $wasteId = $model->wasteId();
        assert(null !== $wasteId);

        return new DailyWasteResponse(
            dailyWasteId: (string) $model->id(),
            propertyId: (string) $propertyId,
            wasteId: (string) $wasteId,
            wasteManagerId: null !== $model->wasteManagerId() ? (string) $model->wasteManagerId() : null,
            purpose: $model->purpose()->value,
            load: $model->load(),
            day: $model->day()->format('Y-m-d'),
        );
    }
}
