<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Export;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;

class RefrigerantGasesExportFilter extends RefrigerantGasFilter
{
    public ?string $emissionFactorGroup = null;
}
