<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GetForReview;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\Shared\Application\CommandBus\Command;

class GetCarbonFootprintReportForReviewRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportId $reportId,
    ) {}
}
