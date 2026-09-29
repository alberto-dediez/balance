<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\List;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;

class ListMitigationMeasureCategoriesResponse
{
    /** @param array<MitigationMeasureCategory> $mitigationMeasureCategories */
    public function __construct(public array $mitigationMeasureCategories = [], public int $totalResults = 0) {}
}
