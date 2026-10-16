<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Export;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;

class EconomicSectorsExportFilter extends EconomicSectorFilter
{
    public ?string $emissionFactorGroup = null;
}
