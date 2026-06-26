<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\List;

class ListCombustiblesResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\Combustible\Combustible> $combustibles */
    public function __construct(public array $combustibles = [], public int $totalResults = 0) {}
}
