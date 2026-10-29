<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepository;

class InMemoryOrganizationalEmployeeContributionRepositoryTest extends OrganizationalEmployeeContributionRepositoryTest
{
    protected function createSUT(): OrganizationalEmployeeContributionRepository
    {
        return OrganizationalEmployeeContributionRepositoryMother::inMemory();
    }
}
