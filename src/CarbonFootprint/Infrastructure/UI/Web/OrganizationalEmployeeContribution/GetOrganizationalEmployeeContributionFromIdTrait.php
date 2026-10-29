<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;

trait GetOrganizationalEmployeeContributionFromIdTrait
{
    private function getOrganizationalEmployeeContributionFromId(
        string $contributionId,
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        OrganizationalEmployeeContributionRepository $repository,
    ): OrganizationalEmployeeContribution {
        $contribution = $repository->find(new OrganizationalEmployeeContributionId($contributionId));

        if (null === $contribution
            || $subjectType !== $contribution->subjectType()
            || $carbonFootprintId !== $contribution->carbonFootprintId()
        ) {
            throw $this->createNotFoundException('Aportación de empleado no encontrada');
        }

        return $contribution;
    }
}
