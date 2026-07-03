<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('carbon_footprint.activity_catalog_provider')]
interface ActivityCatalogProvider
{
    public function activityType(): ActivityType;

    /** @return list<object> */
    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array;

    /** @return list<object> */
    public function secondaryCatalog(): array;
}
