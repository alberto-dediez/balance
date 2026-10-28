<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\List;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

final class ListOrganizationalProviderContributionsRequest
{
    public function __construct(
        public readonly CarbonFootprintReportSubjectType $subjectType,
        public readonly string $carbonFootprintId,
    ) {}
}
