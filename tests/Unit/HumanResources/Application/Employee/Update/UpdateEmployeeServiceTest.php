<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\Update;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Application\Employee\Update\UpdateEmployeeRequest;
use App\HumanResources\Application\Employee\Update\UpdateEmployeeService;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateEmployeeServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $employee = EmployeeMother::registeredEmployee();
        $repository->add($employee);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmployeeRequest(
            employeeId: $employee->id(),
            name: 'Jane Doe',
            email: 'jane@example.com',
            phone: '+0987654321',
            accountId: new AccountId('account-id-test'),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals($employee->id(), $result->employeeId);
        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals('jane@example.com', $result->email);

        $employeePersisted = $repository->find($result->employeeId);

        $this->assertNotNull($employeePersisted);
        $this->assertEquals('Jane Doe', $employeePersisted->name());
        $this->assertEquals('jane@example.com', $employeePersisted->email());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmployeeUpdated);
        $this->assertEquals('Jane Doe', $lastEvent->name());
        $this->assertEquals('jane@example.com', $lastEvent->email());
    }

    public function testIsHandled(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $employee = EmployeeMother::registeredEmployee();

        $result = $sut->isHandled(new UpdateEmployeeRequest(
            employeeId: $employee->id(),
            name: 'test',
            email: 'test@example.com',
            accountId: new AccountId('account-id-test'),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EmployeeRepository $repository): UpdateEmployeeService
    {
        return new UpdateEmployeeService(
            employeeRepository: $repository,
            authorizer: new EmployeeAuthorizer(),
        );
    }
}
