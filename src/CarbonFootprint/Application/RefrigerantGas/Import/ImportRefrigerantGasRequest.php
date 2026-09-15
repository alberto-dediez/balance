<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Import;

use App\Shared\Application\CommandBus\Command;

class ImportRefrigerantGasRequest implements Command
{
    public function __construct(
        public ?string $refrigerantGas = null,
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
