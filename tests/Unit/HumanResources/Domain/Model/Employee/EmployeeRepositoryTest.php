<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\Model\Employee\EmployeeFilter;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EmployeeRepositoryTest extends KernelTestCase
{
    public function testAddEmployeeToRepository(): void
    {
        $repository = $this->createSUT();

        $employee = EmployeeMother::registeredEmployee();

        $this->assertNull($repository->find($employee->id()));

        $repository->add($employee);

        $recoveredEmployee = $repository->find($employee->id());

        $this->assertNotNull($recoveredEmployee);
        $this->assertEquals($employee->id(), $recoveredEmployee->id());
    }

    public function testRemoveEmployeeFromRepository(): void
    {
        $repository = $this->createSUT();

        $employee = EmployeeMother::registeredEmployee();

        $repository->add($employee);

        $recoveredEmployee = $repository->find($employee->id());

        $this->assertNotNull($recoveredEmployee);
        $this->assertEquals($employee->id(), $recoveredEmployee->id());

        $repository->remove($employee);

        $this->assertNull($repository->find($employee->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com'));

        $filter = new EmployeeFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new EmployeeFilter(name: 'John Doe');

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithEmailFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmployeeMother::fromNameAndEmail('Employee 1', 'emp1@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('Employee 2', 'emp2@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('Employee 3', 'other@example.com'));

        $filter = new EmployeeFilter(email: 'emp');

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::random());
        $repository->add(EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com'));

        $filter = new EmployeeFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new EmployeeFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $employee = EmployeeMother::registeredEmployee();

        $repository->add($employee);

        $recoveredEmployee = $repository->find($employee->id());

        $this->assertNotNull($recoveredEmployee);

        $recoveredEmployee->remove();

        $repository->add($employee);

        $this->assertNull($repository->find($employee->id()));
    }

    abstract protected function createSUT(): EmployeeRepository;
}
