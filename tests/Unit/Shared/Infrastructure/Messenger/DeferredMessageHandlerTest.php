<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
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

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->userRepository = new InMemoryUserRepository();
        $this->loggedUser = new SpyLoggedUser();
        $this->captureHandler = new SluttyCommandHandler();
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

        $forced = $this->loggedUser->getForcedUser();
        $this->assertNotNull($forced);
        $this->assertTrue($user->id()->equals($forced->id()));
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
        );
    }
}

class FakeCommandForMessageHandler implements Command {}

class SpyLoggedUser extends LoggedUser
{
    private ?User $forcedUserSpy = null;

    public function __construct() {}

    public function forceUser(?User $user): void
    {
        $this->forcedUserSpy = $user;
    }

    public function getForcedUser(): ?User
    {
        return $this->forcedUserSpy;
    }
}
