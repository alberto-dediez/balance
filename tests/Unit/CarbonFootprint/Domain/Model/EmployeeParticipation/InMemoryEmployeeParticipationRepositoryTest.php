<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;

class InMemoryEmployeeParticipationRepositoryTest extends EmployeeParticipationRepositoryTest
{
    protected function createSUT(): EmployeeParticipationRepository
    {
        return EmployeeParticipationRepositoryMother::inMemory();
    }
}
