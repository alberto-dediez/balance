<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepository;

class InMemoryOrganizationalEmployeeParticipationRepositoryTest extends OrganizationalEmployeeParticipationRepositoryTest
{
    protected function createSUT(): OrganizationalEmployeeParticipationRepository
    {
        return OrganizationalEmployeeParticipationRepositoryMother::inMemory();
    }
}
