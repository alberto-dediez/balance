<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;

interface OrganizationalEmployeeParticipationRepository
{
    public function add(OrganizationalEmployeeParticipation $participation): void;

    public function find(OrganizationalEmployeeParticipationId $participationId): ?OrganizationalEmployeeParticipation;

    public function findByCarbonFootprint(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): ?OrganizationalEmployeeParticipation;
}
