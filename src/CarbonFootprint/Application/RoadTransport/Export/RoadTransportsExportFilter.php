<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Export;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;

class RoadTransportsExportFilter extends RoadTransportFilter
{
    public ?string $emissionFactorGroup = null;
}
