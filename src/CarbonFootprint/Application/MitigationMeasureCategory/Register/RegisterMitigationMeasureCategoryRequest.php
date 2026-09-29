<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterMitigationMeasureCategoryRequest implements Command
{
    public function __construct(
        public string $name = '',
    ) {}
}
