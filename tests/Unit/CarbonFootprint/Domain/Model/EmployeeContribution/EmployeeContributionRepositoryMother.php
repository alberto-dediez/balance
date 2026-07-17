<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;

class EmployeeContributionRepositoryMother
{
    public static function inMemory(): InMemoryEmployeeContributionRepository
    {
        return new InMemoryEmployeeContributionRepository();
    }
}
