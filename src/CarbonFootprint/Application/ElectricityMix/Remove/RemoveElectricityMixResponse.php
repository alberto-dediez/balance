<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Remove;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveElectricityMixResponse implements CommandResponse
{
    public function __construct(public ElectricityMixId $electricityMixId) {}
}
