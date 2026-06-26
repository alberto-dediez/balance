<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Import;

use App\Shared\Application\CommandBus\Command;

class ImportCateringItemRequest implements Command
{
    public function __construct(
        public ?string $cateringItem = null,
        public ?string $name = null,
        public ?string $category = null,
        public ?string $unit = null,
        public ?string $unitWeight = null,
        public ?string $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $recycledCo2eq = null,
        public ?string $recycledCo2 = null,
        public ?string $recycledCh4 = null,
        public ?string $recycledN2o = null,
        public ?string $source = null,
    ) {}
}
