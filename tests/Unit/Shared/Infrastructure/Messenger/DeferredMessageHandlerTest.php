<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\TimedCommand;
use App\Shared\Infrastructure\Messenger\DeferredMessage;
use App\Shared\Infrastructure\Messenger\DeferredMessageHandler;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class DeferredMessageHandlerTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private SpyLoggedUser $loggedUser;
    private SluttyCommandHandler $captureHandler;
    private SpyImpersonatedAccount $impersonatedAccount;
    private SpyLoggedAccount $loggedAccount;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->userRepository = new InMemoryUserRepository();
        $this->loggedUser = new SpyLoggedUser();
        $this->captureHandler = new SluttyCommandHandler();
        $this->impersonatedAccount = new SpyImpersonatedAccount();
        $this->loggedAccount = new SpyLoggedAccount();
    }

    public function testDispatchesInnerCommandToCommandBusWithCorrectUser(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        $inner = new FakeCommandForMessageHandler();
        $message = new DeferredMessage($inner, $user->id());

        $this->createSUT()($message);

        $commands = $this->captureHandler->commands();
        $this->assertCount(1, $commands);
        $this->assertSame($inner, $commands[0]['command']);
        $this->assertTrue($user->id()->equals($commands[0]['user']->id()));
    }

    public function testForcesLoggedUserBeforeDispatch(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id());

        $this->createSUT()($message);

        $forcedCalls = $this->loggedUser->forcedUserCalls();
        $this->assertNotEmpty($forcedCalls);
        $this->assertNotNull($forcedCalls[0]);
        $this->assertTrue($user->id()->equals($forcedCalls[0]->id()));
    }

    public function testClearsForcedUserAfterDispatch(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id());

        $this->createSUT()($message);

        $this->assertNull($this->loggedUser->getForcedUser());
    }

    public function testClearsForcedUserEvenWhenDispatchThrows(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        CommandBusDispatcher::instance()->reset();
        new ThrowingCommandHandlerForMessageHandler();

        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id());

        try {
            $this->createSUT()($message);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertNull($this->loggedUser->getForcedUser());
    }

    public function testForcesImpersonatedAccountBeforeDispatchAndClearsItAfterwards(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        $accountId = AccountId::generate();
        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id(), $accountId);

        $this->createSUT()($message);

        $forcedCalls = $this->impersonatedAccount->forceCalls();
        $this->assertCount(2, $forcedCalls);
        $this->assertSame($accountId, $forcedCalls[0][0]);
        $forcedImpersonatorId = $forcedCalls[0][1];
        $this->assertInstanceOf(UserId::class, $forcedImpersonatorId);
        $this->assertTrue($user->id()->equals($forcedImpersonatorId));
        $this->assertNull($forcedCalls[1][0]);
        $this->assertNull($forcedCalls[1][1]);
        $this->assertSame(2, $this->loggedAccount->resetCalls());
    }

    public function testClearsForcedImpersonatedAccountEvenWhenDispatchThrows(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        CommandBusDispatcher::instance()->reset();
        new ThrowingCommandHandlerForMessageHandler();

        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id(), AccountId::generate());

        try {
            $this->createSUT()($message);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException) {
            // expected
        }

        $forcedCalls = $this->impersonatedAccount->forceCalls();
        $this->assertNull($forcedCalls[count($forcedCalls) - 1][0]);
    }

    public function testDispatchesTimedCommandsWithTheExecutionTimeInsteadOfTheDispatchTime(): void
    {
        $user = UserMother::adminUser();
        $this->userRepository->add($user);

        $dispatchedAt = new \DateTimeImmutable('2020-01-01 07:00');
        $message = new DeferredMessage(new FakeTimedCommandForMessageHandler($dispatchedAt), $user->id());

        $before = new \DateTimeImmutable('NOW');
        $this->createSUT()($message);
        $after = new \DateTimeImmutable('NOW');

        $commands = $this->captureHandler->commands();
        $this->assertCount(1, $commands);
        $command = $commands[0]['command'];
        $this->assertInstanceOf(FakeTimedCommandForMessageHandler::class, $command);
        $this->assertGreaterThanOrEqual($before, $command->now);
        $this->assertLessThanOrEqual($after, $command->now);
    }

    public function testThrowsInvalidArgumentExceptionWhenUserNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = UserMother::adminUser();
        $message = new DeferredMessage(new FakeCommandForMessageHandler(), $user->id());

        $this->createSUT()($message);
    }

    private function createSUT(): DeferredMessageHandler
    {
        return new DeferredMessageHandler(
            userRepository: $this->userRepository,
            loggedUser: $this->loggedUser,
            impersonatedAccount: $this->impersonatedAccount,
            loggedAccount: $this->loggedAccount,
        );
    }
}

class FakeCommandForMessageHandler implements Command {}

class FakeTimedCommandForMessageHandler implements TimedCommand
{
    public function __construct(
        public \DateTimeImmutable $now,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return new self($now);
    }
}

class ThrowingCommandHandlerForMessageHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct()
    {
        $this->register();
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        throw new \RuntimeException('Something went wrong while handling the command');
    }

    public function isHandled(Command $command): bool
    {
        return true;
    }
}

class SpyImpersonatedAccount extends ImpersonatedAccount
{
    /** @var list<array{0: ?AccountId, 1: ?UserId}> */
    private array $forceCalls = [];

    public function __construct() {}

    public function force(?AccountId $accountId, ?UserId $impersonatorId): void
    {
        $this->forceCalls[] = [$accountId, $impersonatorId];
    }

    /** @return list<array{0: ?AccountId, 1: ?UserId}> */
    public function forceCalls(): array
    {
        return $this->forceCalls;
    }
}

class SpyLoggedAccount extends LoggedAccount
{
    private int $resetCalls = 0;

    public function __construct() {}

    public function reset(): void
    {
        ++$this->resetCalls;
    }

    public function resetCalls(): int
    {
        return $this->resetCalls;
    }
}

class SpyLoggedUser extends LoggedUser
{
    private ?User $forcedUserSpy = null;

    /** @var array<int, ?User> */
    private array $forcedUserCalls = [];

    public function __construct() {}

    public function forceUser(?User $user): void
    {
        $this->forcedUserSpy = $user;
        $this->forcedUserCalls[] = $user;
    }

    public function getForcedUser(): ?User
    {
        return $this->forcedUserSpy;
    }

    /** @return array<int, ?User> */
    public function forcedUserCalls(): array
    {
        return $this->forcedUserCalls;
    }
}
