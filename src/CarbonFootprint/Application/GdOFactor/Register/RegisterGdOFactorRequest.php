<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterGdOFactorRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
