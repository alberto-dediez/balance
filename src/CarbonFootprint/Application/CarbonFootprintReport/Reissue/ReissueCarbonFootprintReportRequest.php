<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Reissue;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class ReissueCarbonFootprintReportRequest implements Command
{
    public function __construct(public readonly CarbonFootprintReportId $reportId) {}
}
