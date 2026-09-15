<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Import;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Shared\Application\CommandBus\CommandResponse;

class ImportElectricityMixResponse implements CommandResponse
{
    public function __construct(
        public ElectricityMixId $electricityMixId,
    ) {}
}
