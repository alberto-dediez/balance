<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\List;

class ListWastesResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\Waste\Waste> $wastes */
    public function __construct(public array $wastes = [], public int $totalResults = 0) {}
}
