<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Import;

use App\Shared\Application\CommandBus\Command;

class ImportOvernightStayRequest implements Command
{
    public function __construct(
        public ?string $overnightStay = null,
        public ?string $name = null,
        public ?string $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
        public ?string $emissionFactorGroup = null,
    ) {}
}
