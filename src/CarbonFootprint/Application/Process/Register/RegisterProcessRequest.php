<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Register;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class RegisterProcessRequest implements Command
{
    public function __construct(
        public string $name = '',
        public Unit $unit = Unit::Piece,
    ) {}
}
