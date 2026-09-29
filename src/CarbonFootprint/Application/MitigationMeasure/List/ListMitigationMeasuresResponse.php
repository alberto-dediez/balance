<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\List;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;

class ListMitigationMeasuresResponse
{
    /** @param array<MitigationMeasure> $mitigationMeasures */
    public function __construct(public array $mitigationMeasures = [], public int $totalResults = 0) {}
}
