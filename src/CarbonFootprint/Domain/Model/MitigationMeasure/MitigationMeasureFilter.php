<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Domain\Model\Filter;

class MitigationMeasureFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?MitigationMeasureCategoryId $categoryId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
