<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel\Export;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;

class MachineryFuelsExportFilter extends MachineryFuelFilter
{
    public function __construct(
        ?string $name = null,
        ?MachineryType $machineryType = null,
        int $limit = 10,
    ) {
        parent::__construct($name, $machineryType, $limit);
    }
}
