<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Export;

use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;

class UseProfilesExportFilter extends UseProfileFilter
{
    public ?string $emissionFactorGroup = null;
}
