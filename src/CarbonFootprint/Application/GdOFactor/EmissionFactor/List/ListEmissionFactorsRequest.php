<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\EmissionFactor\List;

class ListEmissionFactorsRequest
{
    public function __construct(public ?int $year = null) {}
}
