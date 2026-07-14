<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Download;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;

final class DownloadEventCarbonFootprintReportRequest
{
    public function __construct(
        public readonly EventCarbonFootprintReportId $reportId,
    ) {}
}
