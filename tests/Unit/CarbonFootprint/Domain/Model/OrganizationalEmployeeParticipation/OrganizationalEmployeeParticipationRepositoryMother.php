<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeParticipationRepository;

class OrganizationalEmployeeParticipationRepositoryMother
{
    public static function inMemory(): InMemoryOrganizationalEmployeeParticipationRepository
    {
        return new InMemoryOrganizationalEmployeeParticipationRepository();
    }
}
