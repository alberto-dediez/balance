<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Register;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Shared\Application\CommandBus\Command;

class RegisterCarbonFootprintReportRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
    ) {}
}
