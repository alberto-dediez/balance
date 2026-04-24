<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\Model\Employee\EmployeeRepository;

class InMemoryEmployeeRepositoryTest extends EmployeeRepositoryTest
{
    protected function createSUT(): EmployeeRepository
    {
        return EmployeeRepositoryMother::inMemory();
    }
}
