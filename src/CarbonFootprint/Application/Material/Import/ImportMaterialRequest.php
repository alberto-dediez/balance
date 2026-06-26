<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Import;

use App\Shared\Application\CommandBus\Command;

class ImportMaterialRequest implements Command
{
    public function __construct(
        public ?string $material = null,
        public ?string $name = null,
        public ?string $category = null,
        public ?string $unit = null,
        public ?string $unitWeight = null,
        public ?string $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}
}
