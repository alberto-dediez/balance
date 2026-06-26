<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\CreateYear;

use App\Shared\Application\CommandBus\CommandResponse;

class CreateEmissionFactorsYearResponse implements CommandResponse
{
    public function __construct(public int $targetYear) {}
}
