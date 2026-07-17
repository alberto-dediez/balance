<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;

class EmployeeParticipationRepositoryMother
{
    public static function inMemory(): InMemoryEmployeeParticipationRepository
    {
        return new InMemoryEmployeeParticipationRepository();
    }
}
