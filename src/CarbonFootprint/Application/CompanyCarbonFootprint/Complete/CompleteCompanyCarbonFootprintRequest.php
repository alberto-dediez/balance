<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Shared\Application\CommandBus\Command;

class CompleteCompanyCarbonFootprintRequest implements Command
{
    public function __construct(
        public CompanyCarbonFootprintId $companyCarbonFootprintId,
    ) {}
}
