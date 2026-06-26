<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\List;

class ListMachineryFuelsResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel> $machineryFuels */
    public function __construct(public array $machineryFuels = [], public int $totalResults = 0) {}
}
