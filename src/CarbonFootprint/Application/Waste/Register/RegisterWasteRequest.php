<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Register;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterWasteRequest implements Command
{
    public function __construct(
        public string $name = '',
        public Unit $unit = Unit::Kilogram,
        public float $density = 1.0,
    ) {}
}
