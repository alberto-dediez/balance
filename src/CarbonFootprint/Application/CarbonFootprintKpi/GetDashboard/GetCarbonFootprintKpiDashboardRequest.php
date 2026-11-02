<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Shared\Application\CommandBus\Command;

class GetCarbonFootprintKpiDashboardRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $subjectId,
        public ?int $year = null,
        public ?IntensityIndicator $comparisonIndicator = null,
    ) {}
}
