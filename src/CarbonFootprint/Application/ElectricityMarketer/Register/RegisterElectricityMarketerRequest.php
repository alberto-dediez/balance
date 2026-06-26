<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterElectricityMarketerRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
