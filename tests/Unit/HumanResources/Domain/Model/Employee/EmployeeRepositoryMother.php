<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Model\Employee;

use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;

class EmployeeRepositoryMother
{
    public static function inMemory(): InMemoryEmployeeRepository
    {
        return new InMemoryEmployeeRepository();
    }
}
