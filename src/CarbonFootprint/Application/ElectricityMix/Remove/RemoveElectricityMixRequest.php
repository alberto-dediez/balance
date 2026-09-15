<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Remove;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Shared\Application\CommandBus\Command;

class RemoveElectricityMixRequest implements Command
{
    public function __construct(public ElectricityMixId $electricityMixId) {}
}
