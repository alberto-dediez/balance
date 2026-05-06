<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterSurplusCauseRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
