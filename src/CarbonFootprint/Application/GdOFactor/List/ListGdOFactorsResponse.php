<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\List;

class ListGdOFactorsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor> $gdOFactors */
    public function __construct(public array $gdOFactors = [], public int $totalResults = 0) {}
}
