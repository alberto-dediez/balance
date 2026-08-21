<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\ImpersonatedAccount;
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
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class DeferredCommandMessengerTest extends TestCase
{
    private FakeMessageBus $messageBus;
    private StubImpersonatedAccount $impersonatedAccount;
    private DeferredCommandMessenger $sut;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->messageBus = new FakeMessageBus();
        $this->impersonatedAccount = new StubImpersonatedAccount();
        $this->sut = new DeferredCommandMessenger($this->messageBus, $this->impersonatedAccount);
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

    public function testHandleDispatchesDeferredMessageWithoutImpersonatedAccountWhenNotImpersonating(): void
    {
        $command = new DeferredCommand(new FakeInnerCommand());

        $this->sut->handle($command, UserMother::adminUser());

        $dispatched = $this->messageBus->lastDispatched();
        $this->assertInstanceOf(DeferredMessage::class, $dispatched);
        $this->assertNull($dispatched->impersonatedAccountId());
    }

    public function testHandleDispatchesDeferredMessageWithImpersonatedAccountWhenImpersonating(): void
    {
        $accountId = AccountId::generate();
        $this->impersonatedAccount->setAccountId($accountId);

        $command = new DeferredCommand(new FakeInnerCommand());

        $this->sut->handle($command, UserMother::adminUser());

        $dispatched = $this->messageBus->lastDispatched();
        $this->assertInstanceOf(DeferredMessage::class, $dispatched);
        $dispatchedAccountId = $dispatched->impersonatedAccountId();
        $this->assertInstanceOf(AccountId::class, $dispatchedAccountId);
        $this->assertTrue($accountId->equals($dispatchedAccountId));
    }

    public function testHandleReturnsEnqueueCommandResponse(): void
    {
        $user = UserMother::adminUser();
        $command = new DeferredCommand(new FakeInnerCommand());

        $result = $this->sut->handle($command, $user);

        $this->assertInstanceOf(EnqueueCommandResponse::class, $result);
    }

    public function testHandleDispatchesWithoutDelayStampWhenNoExecuteAtGiven(): void
    {
        $command = new DeferredCommand(new FakeInnerCommand());

        $this->sut->handle($command, UserMother::adminUser());

        $this->assertNull($this->messageBus->lastStampOfType(DelayStamp::class));
    }

    public function testHandleAddsDelayStampWhenExecuteAtIsInTheFuture(): void
    {
        $executeAt = (new \DateTimeImmutable('NOW'))->modify('+10 minutes');
        $command = new DeferredCommand(new FakeInnerCommand(), executeAt: $executeAt);

        $this->sut->handle($command, UserMother::adminUser());

        $stamp = $this->messageBus->lastStampOfType(DelayStamp::class);
        $this->assertInstanceOf(DelayStamp::class, $stamp);
        // ~10 minutes in milliseconds, tolerating the second elapsed during the test.
        $this->assertGreaterThan(590_000, $stamp->getDelay());
        $this->assertLessThanOrEqual(600_000, $stamp->getDelay());
    }

    public function testHandleDoesNotAddDelayStampWhenExecuteAtIsInThePast(): void
    {
        $executeAt = (new \DateTimeImmutable('NOW'))->modify('-10 minutes');
        $command = new DeferredCommand(new FakeInnerCommand(), executeAt: $executeAt);

        $this->sut->handle($command, UserMother::adminUser());

        $this->assertNull($this->messageBus->lastStampOfType(DelayStamp::class));
    }
}

class FakeInnerCommand implements Command {}

class StubImpersonatedAccount extends ImpersonatedAccount
{
    private ?AccountId $accountId = null;

    public function __construct() {}

    public function setAccountId(?AccountId $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }
}

class FakeMessageBus implements MessageBusInterface
{
    /** @var object[] */
    private array $dispatched = [];

    /** @var list<StampInterface[]> */
    private array $stamps = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;
        $this->stamps[] = $stamps;

        return new Envelope($message, $stamps);
    }

    public function lastDispatched(): ?object
    {
        if ([] === $this->dispatched) {
            return null;
        }

        return $this->dispatched[count($this->dispatched) - 1];
    }

    /**
     * @template T of StampInterface
     *
     * @param class-string<T> $stampClass
     *
     * @return T|null
     */
    public function lastStampOfType(string $stampClass): ?StampInterface
    {
        if ([] === $this->stamps) {
            return null;
        }

        foreach ($this->stamps[count($this->stamps) - 1] as $stamp) {
            if ($stamp instanceof $stampClass) {
                return $stamp;
            }
        }

        return null;
    }
}
