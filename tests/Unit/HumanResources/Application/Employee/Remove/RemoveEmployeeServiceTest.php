<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\Remove;

use App\HumanResources\Application\Employee\EmployeeAuthorizer;
use App\HumanResources\Application\Employee\Remove\RemoveEmployeeRequest;
use App\HumanResources\Application\Employee\Remove\RemoveEmployeeService;
use App\HumanResources\Domain\Model\Employee\EmployeeRemoved;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEmployeeServiceTest extends TestCase
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

        $request = new RemoveEmployeeRequest(
            employeeId: $employee->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals($employee->id(), $result->employeeId);

        $employeePersisted = $repository->find($result->employeeId);

        $this->assertNull($employeePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EmployeeRemoved);
        $this->assertTrue($employee->id()->equals($lastEvent->employeeId()));
    }

    public function testIsHandled(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $employee = EmployeeMother::registeredEmployee();

        $result = $sut->isHandled(new RemoveEmployeeRequest(
            employeeId: $employee->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EmployeeRepository $repository): RemoveEmployeeService
    {
        return new RemoveEmployeeService(
            repository: $repository,
            authorizer: new EmployeeAuthorizer(),
        );
    }
}
