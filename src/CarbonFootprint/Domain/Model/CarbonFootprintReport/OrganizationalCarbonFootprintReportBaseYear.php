<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final readonly class OrganizationalCarbonFootprintReportBaseYear
{
    public function __construct(
        public int $year,
        public string $baseYearTotalKgCo2eq,
        public string $recalculationThresholdPercent,
        public string $justification,
    ) {}
}
