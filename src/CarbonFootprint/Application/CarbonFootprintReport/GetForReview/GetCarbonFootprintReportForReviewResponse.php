<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GetForReview;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\Emissions;

class GetCarbonFootprintReportForReviewResponse
{
    public function __construct(
        public readonly CarbonFootprintReport $report,
        public readonly string $subjectName,
        public readonly int $calculationYear,
        public readonly Emissions $totalEmissions,
    ) {}
}
