<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\Import;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Shared\Application\CommandBus\Command;

class ImportMitigationActionsRequest implements Command
{
    public function __construct(
        public CarbonFootprintReportSubjectType $subjectType,
        public string $carbonFootprintId,
        public bool $onlyIfEmpty = false,
    ) {}
}
