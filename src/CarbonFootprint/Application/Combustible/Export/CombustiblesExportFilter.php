<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Export;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;

class CombustiblesExportFilter extends CombustibleFilter
{
    public ?string $emissionFactorGroup = null;
}
