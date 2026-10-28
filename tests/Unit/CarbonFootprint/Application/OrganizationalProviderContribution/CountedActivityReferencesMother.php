<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferences;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;

class CountedActivityReferencesMother
{
    public static function create(?OrganizationalProviderContributionRepository $contributionRepository = null): CountedActivityReferences
    {
        return new CountedActivityReferences($contributionRepository ?? OrganizationalProviderContributionRepositoryMother::inMemory());
    }
}
