<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

class RemoveMitigationActionsOfCarbonFootprintRequest
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
