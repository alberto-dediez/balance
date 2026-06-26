<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Import;

use App\Shared\Application\CommandBus\Command;

class ImportCombustibleRequest implements Command
{
    public function __construct(
        public ?string $combustible = null,
        public ?string $name = null,
        public ?string $unit = null,
        public ?string $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}
}
