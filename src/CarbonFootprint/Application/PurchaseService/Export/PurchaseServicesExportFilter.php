<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Export;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;

class PurchaseServicesExportFilter extends PurchaseServiceFilter
{
    public ?string $emissionFactorGroup = null;
}
