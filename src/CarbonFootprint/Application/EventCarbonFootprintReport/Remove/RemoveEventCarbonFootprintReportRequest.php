<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class RemoveEventCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintReportId $reportId,
    ) {}
}
