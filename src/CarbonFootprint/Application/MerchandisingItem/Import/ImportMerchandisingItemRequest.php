<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Import;

use App\Shared\Application\CommandBus\Command;

class ImportMerchandisingItemRequest implements Command
{
    public function __construct(
        public ?string $merchandisingItem = null,
        public ?string $name = null,
        public ?string $category = null,
        public ?string $unit = null,
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
        public ?string $emissionFactorGroup = null,
    ) {}
}
