<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\UnlinkUser;

use App\HumanResources\Application\Employee\UnlinkUser\UnlinkUserRequest;
use App\HumanResources\Application\Employee\UnlinkUser\UnlinkUserService;
use App\HumanResources\Domain\Model\Employee\EmployeeAndUserUnlinked;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UnlinkUserServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUnlinksUserFromEmployee(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $employee = EmployeeMother::registeredEmployee();
        $employee->linkUser(UserId::generate());
        $repository->add($employee);

        $sut = $this->createSUT($repository);
        $result = $sut(new UnlinkUserRequest($employee->id()));

        $this->assertTrue($employee->id()->equals($result->employeeId));

        $persisted = $repository->find($employee->id());
        $this->assertNotNull($persisted);
        $this->assertNull($persisted->userId());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(EmployeeAndUserUnlinked::class, $lastEvent);
        $this->assertTrue($employee->id()->equals($lastEvent->employeeId()));
    }

    public function testUnlinksEmployeeWithNoExistingUser(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $employee = EmployeeMother::registeredEmployee();
        $repository->add($employee);

        $sut = $this->createSUT($repository);
        $result = $sut(new UnlinkUserRequest($employee->id()));

        $this->assertTrue($employee->id()->equals($result->employeeId));
        $this->assertNull($repository->find($employee->id())?->userId());
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT($repository)(new UnlinkUserRequest(EmployeeId::generate()));
    }

    private function createSUT(EmployeeRepository $repository): UnlinkUserService
    {
        return new UnlinkUserService(employeeRepository: $repository);
    }
}
