<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\LinkUser\LinkUserService;
use App\HumanResources\Domain\Event\Employee\LinkUserOnEmployeeEventSubscriber;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Domain\Model\Employee\EmployeeRegistered;
use App\HumanResources\Domain\Model\Employee\EmployeeRemoved;
use App\HumanResources\Domain\Model\Employee\EmployeeUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkUserOnEmployeeEventSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEmployeeRegistered(): void
    {
        $sut = $this->createSUT(new InMemoryUserRepository());

        $this->assertTrue($sut->isSubscribedTo($this->makeRegisteredEvent(EmployeeId::generate(), 'john@example.com', AccountId::generate())));
    }

    public function testIsSubscribedToEmployeeUpdated(): void
    {
        $sut = $this->createSUT(new InMemoryUserRepository());

        $this->assertTrue($sut->isSubscribedTo($this->makeUpdatedEvent(EmployeeId::generate(), 'john@example.com', AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(new InMemoryUserRepository());

        $this->assertFalse($sut->isSubscribedTo(new EmployeeRemoved(EmployeeId::generate())));
    }

    public function testHandleEmployeeRegisteredLinksUserWhenEmailMatchesAndNoUserId(): void
    {
        $accountId = AccountId::generate();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('john@example.com', Role::Owner, $accountId));

        $event = $this->makeRegisteredEvent(EmployeeId::generate(), 'john@example.com', $accountId, null);

        $sut = $this->createSUT($userRepository, expectedCalls: 1);
        $sut->handle($event);
    }

    public function testHandleEmployeeRegisteredDoesNotLinkWhenUserIdAlreadySet(): void
    {
        $accountId = AccountId::generate();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('john@example.com', Role::Owner, $accountId));

        $event = $this->makeRegisteredEvent(EmployeeId::generate(), 'john@example.com', $accountId, UserId::generate());

        $sut = $this->createSUT($userRepository, expectedCalls: 0);
        $sut->handle($event);
    }

    public function testHandleEmployeeRegisteredDoesNotLinkWhenUserNotFound(): void
    {
        $accountId = AccountId::generate();

        $event = $this->makeRegisteredEvent(EmployeeId::generate(), 'unknown@example.com', $accountId, null);

        $sut = $this->createSUT(new InMemoryUserRepository(), expectedCalls: 0);
        $sut->handle($event);
    }

    public function testHandleEmployeeRegisteredDoesNotLinkWhenUserBelongsToDifferentAccount(): void
    {
        $accountId = AccountId::generate();
        $otherAccountId = AccountId::generate();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('john@example.com', Role::Owner, $otherAccountId));

        $event = $this->makeRegisteredEvent(EmployeeId::generate(), 'john@example.com', $accountId, null);

        $sut = $this->createSUT($userRepository, expectedCalls: 0);
        $sut->handle($event);
    }

    public function testHandleEmployeeUpdatedLinksUserWhenEmailMatchesAndNoUserId(): void
    {
        $accountId = AccountId::generate();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('updated@example.com', Role::Owner, $accountId));

        $event = $this->makeUpdatedEvent(EmployeeId::generate(), 'updated@example.com', $accountId, null);

        $sut = $this->createSUT($userRepository, expectedCalls: 1);
        $sut->handle($event);
    }

    public function testHandleEmployeeUpdatedDoesNotLinkWhenUserIdAlreadySet(): void
    {
        $accountId = AccountId::generate();

        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('updated@example.com', Role::Owner, $accountId));

        $event = $this->makeUpdatedEvent(EmployeeId::generate(), 'updated@example.com', $accountId, UserId::generate());

        $sut = $this->createSUT($userRepository, expectedCalls: 0);
        $sut->handle($event);
    }

    private function makeRegisteredEvent(EmployeeId $employeeId, string $email, AccountId $accountId, ?UserId $userId = null): EmployeeRegistered
    {
        return new EmployeeRegistered(
            employeeId: $employeeId,
            name: 'John Doe',
            email: $email,
            phone: null,
            address: null,
            accountId: $accountId,
            userId: $userId,
        );
    }

    private function makeUpdatedEvent(EmployeeId $employeeId, string $email, AccountId $accountId, ?UserId $userId = null): EmployeeUpdated
    {
        return new EmployeeUpdated(
            employeeId: $employeeId,
            name: 'John Doe',
            email: $email,
            phone: null,
            address: null,
            accountId: $accountId,
            userId: $userId,
        );
    }

    private function createSUT(InMemoryUserRepository $userRepository, int $expectedCalls = 0): LinkUserOnEmployeeEventSubscriber
    {
        /** @var LinkUserService&MockObject $linkUser */
        $linkUser = $this->createMock(LinkUserService::class);
        $linkUser->expects($this->exactly($expectedCalls))->method('__invoke');

        return new LinkUserOnEmployeeEventSubscriber($userRepository, $linkUser);
    }
}
