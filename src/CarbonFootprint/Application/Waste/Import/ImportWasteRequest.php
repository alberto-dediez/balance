<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Import;

use App\Shared\Application\CommandBus\Command;

class ImportWasteRequest implements Command
{
    public function __construct(
        public ?string $waste = null,
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
