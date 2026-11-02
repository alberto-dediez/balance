<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi\ListSubjects;

use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectOption;
use App\Shared\Application\CommandBus\CommandResponse;

class ListKpiSubjectsResponse implements CommandResponse
{
    /** @param list<KpiSubjectOption> $options */
    public function __construct(
        public array $options,
    ) {}
}
