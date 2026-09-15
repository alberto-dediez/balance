<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterRefrigerantGasRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
