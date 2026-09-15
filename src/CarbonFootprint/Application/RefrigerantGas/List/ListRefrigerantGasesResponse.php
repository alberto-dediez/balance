<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\List;

class ListRefrigerantGasesResponse
{
    /** @param array<\App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas> $refrigerantGases */
    public function __construct(public array $refrigerantGases = [], public int $totalResults = 0) {}
}
