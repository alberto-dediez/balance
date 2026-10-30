<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class ChangeCompanyCarbonFootprintPrecalculatedModeRequest implements Command
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
        public bool $precalculated,
    ) {}
}
