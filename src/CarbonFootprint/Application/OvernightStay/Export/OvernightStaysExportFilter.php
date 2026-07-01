<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Export;

use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;

class OvernightStaysExportFilter extends OvernightStayFilter
{
    public ?string $emissionFactorGroup = null;
}
