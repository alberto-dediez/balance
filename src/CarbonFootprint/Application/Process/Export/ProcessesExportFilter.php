<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Export;

use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;

class ProcessesExportFilter extends ProcessFilter
{
    public ?string $emissionFactorGroup = null;
}
