<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Export;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;

class ElectricityMixesExportFilter extends ElectricityMixFilter
{
    public ?string $emissionFactorGroup = null;
}
