<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\List;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Application\Employee\List\ListEmployeesRequest;
use App\HumanResources\Application\Employee\List\ListEmployeesService;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmployeesServiceTest extends TestCase
{
    public function testListAllEmployees(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $repository->add(EmployeeMother::registeredEmployee());
        $repository->add(EmployeeMother::fromNameAndEmail('Jane Doe', 'jane@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('John Smith', 'smith@example.com'));

        $sut = $this->createSUT($repository);

        $request = new ListEmployeesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->employees);
        $this->assertEquals(3, $result->totalResults);
    }

    public function testListEmployeesByName(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $repository->add(EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('Jane Doe', 'jane@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('John Smith', 'smith@example.com'));

        $sut = $this->createSUT($repository);

        $request = new ListEmployeesRequest(name: 'John');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(2, $result->employees);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testListEmployeesByEmail(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $repository->add(EmployeeMother::fromNameAndEmail('Employee 1', 'emp1@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('Employee 2', 'emp2@example.com'));
        $repository->add(EmployeeMother::fromNameAndEmail('Employee 3', 'other@example.com'));

        $sut = $this->createSUT($repository);

        $request = new ListEmployeesRequest(email: 'emp');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(2, $result->employees);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testListEmployeesFilteredByAccount(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $repository->add(EmployeeMother::from(
            name: 'Employee 1',
            email: 'emp1@example.com',
            id: '1',
            accountId: 'account-1',
        ));
        $repository->add(EmployeeMother::from(
            name: 'Employee 2',
            email: 'emp2@example.com',
            id: '2',
            accountId: 'account-2',
        ));
        $repository->add(EmployeeMother::from(
            name: 'Employee 3',
            email: 'emp3@example.com',
            id: '3',
            accountId: 'account-1',
        ));

        $sut = $this->createSUT($repository);

        $request = new ListEmployeesRequest(accountId: new AccountId('account-1'));

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(2, $result->employees);
        $this->assertEquals(2, $result->totalResults);
    }

    private function createSUT(EmployeeRepository $repository): ListEmployeesService
    {
        return new ListEmployeesService(
            repository: $repository,
            authorizer: new EmployeeAuthorizer(),
        );
    }
}
