<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;

class OrganizationalEmployeeContributionRepositoryMother
{
    public static function inMemory(): InMemoryOrganizationalEmployeeContributionRepository
    {
        return new InMemoryOrganizationalEmployeeContributionRepository();
    }
}
