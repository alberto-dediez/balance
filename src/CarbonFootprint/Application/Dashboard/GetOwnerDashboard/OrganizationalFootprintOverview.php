<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;

final class OrganizationalFootprintOverview
{
    public function __construct(
        public readonly PropertyCarbonFootprint|CompanyCarbonFootprint $footprint,
        public readonly CarbonFootprintReportSubjectType $subjectType,
        public readonly ?string $subjectName,
    ) {}
}
