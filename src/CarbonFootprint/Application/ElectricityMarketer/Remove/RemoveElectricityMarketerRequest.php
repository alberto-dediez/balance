<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Remove;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\Shared\Application\CommandBus\Command;

class RemoveElectricityMarketerRequest implements Command
{
    public function __construct(public ElectricityMarketerId $electricityMarketerId) {}
}
