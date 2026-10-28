<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class ReportedEmissionsCatalogProvider implements ActivityCatalogProvider
{
    public function activityType(): ActivityType
    {
        return ActivityType::ReportedEmissions;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }

    public function secondaryCatalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }
}
