<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class ReissueEventCarbonFootprintReportRequest implements Command
{
    public function __construct(public readonly EventCarbonFootprintReportId $reportId) {}
}
