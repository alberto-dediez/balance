<?php

declare(strict_types=1);

namespace App\Tests\Unit\HumanResources\Domain\Event\Employee;

use App\HumanResources\Application\Employee\LinkUser\LinkUserService;
use App\HumanResources\Application\Employee\Register\RegisterEmployeeRequest;
use App\HumanResources\Domain\Event\Employee\LinkEmployeeOnUserRegisteredSubscriber;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkEmployeeOnUserRegisteredSubscriberTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testIsSubscribedToUserRegistered(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo($this->makeUserRegisteredEvent('john@example.com', AccountId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new UserRemoved(UserId::generate())));
    }

    public function testHandleUserRegisteredLinksEmployeeWhenEmailMatchesAndNoUserId(): void
    {
        $accountIdString = (string) AccountId::generate();
        $accountId = new AccountId($accountIdString);

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::from(
            name: 'John Doe',
            email: 'john@example.com',
            id: (string) EmployeeId::generate(),
            accountId: $accountIdString,
        ));

        $event = $this->makeUserRegisteredEvent('john@example.com', $accountId);

        $sut = $this->createSUT($employeeRepository, expectedLinkCalls: 1);
        $sut->handle($event);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testHandleUserRegisteredDispatchesRegisterEmployeeWhenNoMatchingEmployeeFound(): void
    {
        $accountId = AccountId::generate();
        $userId = UserId::generate();

        $event = new UserRegistered(
            userId: $userId,
            username: 'new@example.com',
            password: 'password',
            accountId: $accountId,
        );

        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), expectedLinkCalls: 0);
        $sut->handle($event);

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);

        $command = $commands[0]['command'];
        $this->assertInstanceOf(RegisterEmployeeRequest::class, $command);
        $this->assertEquals('new@example.com', $command->email);
        $this->assertEquals('new@example.com', $command->name);
        $this->assertEquals((string) $accountId, (string) $command->accountId);
        $this->assertEquals((string) $userId, (string) $command->userId);
    }

    public function testHandleUserRegisteredDoesNotDispatchWhenUserHasNoAccount(): void
    {
        $employeeRepository = EmployeeRepositoryMother::inMemory();

        $event = new UserRegistered(
            userId: UserId::generate(),
            username: 'john@example.com',
            password: 'password',
            accountId: null,
        );

        $sut = $this->createSUT($employeeRepository, expectedLinkCalls: 0);
        $sut->handle($event);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testHandleUserRegisteredDoesNotLinkWhenEmployeeAlreadyLinked(): void
    {
        $accountIdString = (string) AccountId::generate();
        $accountId = new AccountId($accountIdString);

        $employeeRepository = new InMemoryEmployeeRepository();

        $employee = EmployeeMother::from(
            name: 'John Doe',
            email: 'john@example.com',
            id: (string) EmployeeId::generate(),
            accountId: $accountIdString,
        );
        $employee->linkUser(UserId::generate());
        $employeeRepository->add($employee);

        $event = $this->makeUserRegisteredEvent('john@example.com', $accountId);

        $sut = $this->createSUT($employeeRepository, expectedLinkCalls: 0);
        $sut->handle($event);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testHandleUserRegisteredDoesNothingWhenHumanResourcesDisabled(): void
    {
        $event = $this->makeUserRegisteredEvent('new@example.com', AccountId::generate());

        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), expectedLinkCalls: 0, humanResourcesEnabled: false);
        $sut->handle($event);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testHandleUserRegisteredDoesNotLinkExistingEmployeeWhenHumanResourcesDisabled(): void
    {
        $accountIdString = (string) AccountId::generate();

        $employeeRepository = EmployeeRepositoryMother::inMemory();
        $employeeRepository->add(EmployeeMother::from(
            name: 'John Doe',
            email: 'john@example.com',
            id: (string) EmployeeId::generate(),
            accountId: $accountIdString,
        ));

        $event = $this->makeUserRegisteredEvent('john@example.com', new AccountId($accountIdString));

        $sut = $this->createSUT($employeeRepository, expectedLinkCalls: 0, humanResourcesEnabled: false);
        $sut->handle($event);

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testIsSubscribedToUserRegisteredEvenWhenDisabled(): void
    {
        $sut = $this->createSUT(EmployeeRepositoryMother::inMemory(), humanResourcesEnabled: false);

        $this->assertTrue($sut->isSubscribedTo($this->makeUserRegisteredEvent('john@example.com', AccountId::generate())));
    }

    private function makeUserRegisteredEvent(string $username, AccountId $accountId): UserRegistered
    {
        return new UserRegistered(
            userId: UserId::generate(),
            username: $username,
            password: 'password',
            accountId: $accountId,
        );
    }

    private function createSUT(
        InMemoryEmployeeRepository $employeeRepository,
        int $expectedLinkCalls = 0,
        bool $humanResourcesEnabled = true,
    ): LinkEmployeeOnUserRegisteredSubscriber {
        /** @var LinkUserService&MockObject $linkUser */
        $linkUser = $this->createMock(LinkUserService::class);
        $linkUser->expects($this->exactly($expectedLinkCalls))->method('__invoke');

        return new LinkEmployeeOnUserRegisteredSubscriber(
            $employeeRepository,
            new InMemoryUserRepository(),
            $linkUser,
            new FakeLoggedUser(UserMother::adminUser()),
            $humanResourcesEnabled,
        );
    }
}
