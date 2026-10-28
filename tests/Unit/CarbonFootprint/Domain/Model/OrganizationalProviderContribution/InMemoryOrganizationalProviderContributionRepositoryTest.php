<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;

class InMemoryOrganizationalProviderContributionRepositoryTest extends OrganizationalProviderContributionRepositoryTest
{
    protected function createSUT(): OrganizationalProviderContributionRepository
    {
        return OrganizationalProviderContributionRepositoryMother::inMemory();
    }
}
