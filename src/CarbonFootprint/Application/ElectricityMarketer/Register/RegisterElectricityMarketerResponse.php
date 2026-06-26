<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Register;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterElectricityMarketerResponse implements CommandResponse
{
    public function __construct(
        public ElectricityMarketerId $electricityMarketerId,
        public string $name,
    ) {}
}
