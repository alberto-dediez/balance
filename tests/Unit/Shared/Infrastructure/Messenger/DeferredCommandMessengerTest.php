<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Infrastructure\Messenger\DeferredCommandMessenger;
use App\Shared\Infrastructure\Messenger\DeferredMessage;
use App\Shared\Infrastructure\Messenger\EnqueueCommandResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class DeferredCommandMessengerTest extends TestCase
{
    private FakeMessageBus $messageBus;
    private DeferredCommandMessenger $sut;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->messageBus = new FakeMessageBus();
        $this->sut = new DeferredCommandMessenger($this->messageBus);
    }

    public function testIsHandledForDeferredCommand(): void
    {
        $command = new DeferredCommand(new FakeInnerCommand());

        $this->assertTrue($this->sut->isHandled($command));
    }

    public function testIsNotHandledForOtherCommands(): void
    {
        $this->assertFalse($this->sut->isHandled(new FakeInnerCommand()));
    }

    public function testHandleDispatchesDeferredMessageToMessageBus(): void
    {
        $user = UserMother::adminUser();
        $inner = new FakeInnerCommand();
        $command = new DeferredCommand($inner);

        $this->sut->handle($command, $user);

        $dispatched = $this->messageBus->lastDispatched();
        $this->assertInstanceOf(DeferredMessage::class, $dispatched);
        $this->assertSame($inner, $dispatched->command());
        $this->assertTrue($user->id()->equals($dispatched->userId()));
    }

    public function testHandleReturnsEnqueueCommandResponse(): void
    {
        $user = UserMother::adminUser();
        $command = new DeferredCommand(new FakeInnerCommand());

        $result = $this->sut->handle($command, $user);

        $this->assertInstanceOf(EnqueueCommandResponse::class, $result);
    }
}

class FakeInnerCommand implements Command {}

class FakeMessageBus implements MessageBusInterface
{
    /** @var object[] */
    private array $dispatched = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;

        return new Envelope($message);
    }

    public function lastDispatched(): ?object
    {
        if ([] === $this->dispatched) {
            return null;
        }

        return $this->dispatched[count($this->dispatched) - 1];
    }
}
