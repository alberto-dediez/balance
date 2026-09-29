<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\GetPlan;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

class GetMitigationPlanRequest
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
