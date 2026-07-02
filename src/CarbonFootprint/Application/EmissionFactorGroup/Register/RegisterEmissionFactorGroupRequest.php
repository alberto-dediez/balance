<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmissionFactorGroup\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterEmissionFactorGroupRequest implements Command
{
    public function __construct(
        public string $name = '',
        public float $gwpCo2 = 1.0,
        public float $gwpCh4 = 0.0,
        public float $gwpN2O = 0.0,
    ) {}
}
