<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class GenerateEventCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public EventCarbonFootprintReportId $reportId,
    ) {}
}
