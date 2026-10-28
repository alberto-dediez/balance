<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;

class OrganizationalProviderContributionRepositoryMother
{
    public static function inMemory(): InMemoryOrganizationalProviderContributionRepository
    {
        return new InMemoryOrganizationalProviderContributionRepository();
    }
}
