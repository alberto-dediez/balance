<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Register;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCarbonFootprintReportResponse implements CommandResponse
{
    public function __construct(
        public CarbonFootprintReportId $reportId,
    ) {}
}
