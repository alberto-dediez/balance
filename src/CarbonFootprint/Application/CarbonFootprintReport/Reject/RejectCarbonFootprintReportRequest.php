<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Reject;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class RejectCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportId $reportId,
        public string $comment,
    ) {}
}
