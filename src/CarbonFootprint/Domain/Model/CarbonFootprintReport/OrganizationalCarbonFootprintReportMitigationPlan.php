<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjection;

final class OrganizationalCarbonFootprintReportMitigationPlan
{
    /** @param list<OrganizationalCarbonFootprintReportMitigationAction> $actions */
    public function __construct(
        public readonly array $actions,
        public readonly MitigationProjection $projection,
    ) {}
}
