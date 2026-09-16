<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\List;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;

class ListPropertyCarbonFootprintsResponse
{
    /** @param PropertyCarbonFootprint[] $propertyCarbonFootprints */
    public function __construct(public array $propertyCarbonFootprints = [], public int $totalResults = 0) {}
}
