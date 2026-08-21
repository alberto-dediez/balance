<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\GetCompensationSummary;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCompensation;
use App\Shared\Application\CommandBus\CommandResponse;

class GetCompensationSummaryResponse implements CommandResponse
{
    public function __construct(public readonly EventCarbonFootprintReportCompensation $compensation) {}
}
