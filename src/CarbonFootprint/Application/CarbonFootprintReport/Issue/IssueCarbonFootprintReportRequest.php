<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Issue;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class IssueCarbonFootprintReportRequest implements Command
{
    /** @param array<string, string> $sections */
    public function __construct(
        public CarbonFootprintReportId $reportId,
        public array $sections = [],
    ) {}
}
