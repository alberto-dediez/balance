<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Export;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodFilter;

class CapitalGoodsExportFilter extends CapitalGoodFilter
{
    public ?string $emissionFactorGroup = null;
}
