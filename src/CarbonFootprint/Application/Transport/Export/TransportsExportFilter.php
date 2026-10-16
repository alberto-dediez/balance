<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Export;

use App\CarbonFootprint\Domain\Model\Transport\TransportFilter;

class TransportsExportFilter extends TransportFilter
{
    public ?string $emissionFactorGroup = null;
}
