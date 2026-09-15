<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\EmissionFactor\Update;

use App\Shared\Application\CommandBus\CommandResponse;

class UpdateEmissionFactorsResponse implements CommandResponse
{
    public function __construct(public int $year) {}
}
