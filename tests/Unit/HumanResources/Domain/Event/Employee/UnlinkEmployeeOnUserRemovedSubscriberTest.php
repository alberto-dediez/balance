<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\UnlinkUser\UnlinkUserService;
use App\HumanResources\Domain\Event\Employee\UnlinkEmployeeOnUserRemovedSubscriber;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UnlinkEmployeeOnUserRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToUserRemoved(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new UserRemoved(UserId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new UserRegistered(
            userId: UserId::generate(),
            username: 'john@example.com',
            password: 'password',
        )));
    }

    public function testHandleUserRemovedUnlinksMatchingEmployee(): void
    {
        $employeeRepository = new InMemoryEmployeeRepository();
        $userId = UserId::generate();

        $employee = EmployeeMother::registeredEmployee();
        $employee->linkUser($userId);
        $employeeRepository->add($employee);

        $sut = $this->createSUT($employeeRepository, expectedCalls: 1);
        $sut->handle(new UserRemoved($userId));
    }

    public function testHandleUserRemovedDoesNothingWhenNoLinkedEmployee(): void
    {
        $employeeRepository = new InMemoryEmployeeRepository();
        $employeeRepository->add(EmployeeMother::registeredEmployee());

        $sut = $this->createSUT($employeeRepository, expectedCalls: 0);
        $sut->handle(new UserRemoved(UserId::generate()));
    }

    public function testHandleUserRemovedDoesNothingWhenRepositoryIsEmpty(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), expectedCalls: 0);
        $sut->handle(new UserRemoved(UserId::generate()));
    }

    public function testHandleUserRemovedOnlyUnlinksEmployeeWithMatchingUserId(): void
    {
        $employeeRepository = new InMemoryEmployeeRepository();

        $targetUserId = UserId::generate();
        $otherUserId = UserId::generate();

        $targetEmployee = EmployeeMother::from('Target', 'target@example.com', (string) EmployeeId::generate(), null);
        $targetEmployee->linkUser($targetUserId);
        $employeeRepository->add($targetEmployee);

        $otherEmployee = EmployeeMother::from('Other', 'other@example.com', (string) EmployeeId::generate(), null);
        $otherEmployee->linkUser($otherUserId);
        $employeeRepository->add($otherEmployee);

        $sut = $this->createSUT($employeeRepository, expectedCalls: 1);
        $sut->handle(new UserRemoved($targetUserId));
    }

    private function createSUT(InMemoryEmployeeRepository $employeeRepository, int $expectedCalls = 0): UnlinkEmployeeOnUserRemovedSubscriber
    {
        /** @var UnlinkUserService&MockObject $unlinkUser */
        $unlinkUser = $this->createMock(UnlinkUserService::class);
        $unlinkUser->expects($this->exactly($expectedCalls))->method('__invoke');

        return new UnlinkEmployeeOnUserRemovedSubscriber($employeeRepository, $unlinkUser);
    }
}
