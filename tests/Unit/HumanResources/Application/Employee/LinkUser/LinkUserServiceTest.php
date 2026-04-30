<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Application\Employee\LinkUser;

use App\HumanResources\Application\Employee\LinkUser\LinkUserRequest;
use App\HumanResources\Application\Employee\LinkUser\LinkUserService;
use App\HumanResources\Domain\Model\Employee\EmployeeAndUserLinked;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class LinkUserServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testLinksUserToEmployee(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();
        $employee = EmployeeMother::registeredEmployee();
        $repository->add($employee);

        $userId = UserId::generate();

        $sut = $this->createSUT($repository);
        $result = $sut(new LinkUserRequest($employee->id(), $userId));

        $this->assertTrue($employee->id()->equals($result->employeeId));
        $this->assertTrue($userId->equals($result->userId));

        $persisted = $repository->find($employee->id());
        $this->assertNotNull($persisted);
        $this->assertNotNull($persisted->userId());
        $this->assertTrue($userId->equals($persisted->userId()));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(EmployeeAndUserLinked::class, $lastEvent);
        $this->assertTrue($employee->id()->equals($lastEvent->employeeId()));
        $this->assertTrue($userId->equals($lastEvent->userId()));
    }

    public function testThrowsWhenEmployeeNotFound(): void
    {
        $repository = EmployeeRepositoryMother::inMemory();

        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT($repository)(new LinkUserRequest(EmployeeId::generate(), UserId::generate()));
    }

    private function createSUT(EmployeeRepository $repository): LinkUserService
    {
        return new LinkUserService(employeeRepository: $repository);
    }
}
