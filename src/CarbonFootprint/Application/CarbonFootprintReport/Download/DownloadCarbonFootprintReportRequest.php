<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Download;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;

final class DownloadCarbonFootprintReportRequest
{
    public function __construct(
        public readonly CarbonFootprintReportId $reportId,
    ) {}
}
