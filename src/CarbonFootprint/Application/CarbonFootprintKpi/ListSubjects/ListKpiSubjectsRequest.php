<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Shared\Application\CommandBus\Command;

class ListKpiSubjectsRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
    ) {}
}
