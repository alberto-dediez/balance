<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Property\Domain\Model\Property\PropertyId;

interface PropertyCarbonFootprintRepository
{
    public function add(PropertyCarbonFootprint $propertyCarbonFootprint): void;

    public function remove(PropertyCarbonFootprint $propertyCarbonFootprint): void;

    public function find(PropertyCarbonFootprintId $propertyCarbonFootprintId): ?PropertyCarbonFootprint;

    public function findByPropertyIdAndYear(PropertyId $propertyId, int $year): ?PropertyCarbonFootprint;

    /** @return array<PropertyCarbonFootprint> */
    public function findByPropertyId(PropertyId $propertyId): array;

    public function findByActivityId(ActivityId $activityId): ?PropertyCarbonFootprint;

    /** @return array<PropertyCarbonFootprint> */
    public function findByFilter(PropertyCarbonFootprintFilter $filter): array;

    public function totalResultsByFilter(PropertyCarbonFootprintFilter $filter): int;
}
