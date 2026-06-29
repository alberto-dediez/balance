<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterOvernightStayRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
