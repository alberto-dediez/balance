<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;

class InMemoryEmployeeContributionRepositoryTest extends EmployeeContributionRepositoryTest
{
    protected function createSUT(): EmployeeContributionRepository
    {
        return EmployeeContributionRepositoryMother::inMemory();
    }
}
