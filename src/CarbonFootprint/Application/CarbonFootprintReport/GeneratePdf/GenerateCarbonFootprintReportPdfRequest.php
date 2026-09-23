<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class GenerateCarbonFootprintReportPdfRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportId $reportId,
    ) {}
}
