<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\List;

class ListMaterialsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\Material\Material> $materials */
    public function __construct(public array $materials = [], public int $totalResults = 0) {}
}
