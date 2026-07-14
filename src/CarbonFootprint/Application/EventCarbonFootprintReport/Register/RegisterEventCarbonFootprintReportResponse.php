<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Register;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterEventCarbonFootprintReportResponse implements CommandResponse
{
    public function __construct(
        public EventCarbonFootprintReportId $reportId,
    ) {}
}
