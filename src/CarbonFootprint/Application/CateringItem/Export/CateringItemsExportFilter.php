<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Export;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;

class CateringItemsExportFilter extends CateringItemFilter
{
    public ?string $emissionFactorGroup = null;
}
