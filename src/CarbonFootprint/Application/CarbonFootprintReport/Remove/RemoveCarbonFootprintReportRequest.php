<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Remove;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class RemoveCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportId $reportId,
    ) {}
}
