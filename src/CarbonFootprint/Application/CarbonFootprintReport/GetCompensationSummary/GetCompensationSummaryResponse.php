<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GetCompensationSummary;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensation;
use App\Shared\Application\CommandBus\CommandResponse;

class GetCompensationSummaryResponse implements CommandResponse
{
    public function __construct(public readonly EventCarbonFootprintReportCompensation $compensation) {}
}
