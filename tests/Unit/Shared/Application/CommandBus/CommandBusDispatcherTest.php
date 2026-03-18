<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CommandBusDispatcherTest extends TestCase
{
    private SluttyCommandHandler $spyHandler;
    private VeryPickyCommandHandler $sluttyHandler;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();

        $this->spyHandler = new SluttyCommandHandler();
        $this->sluttyHandler = new VeryPickyCommandHandler();
    }

    public function testDispatchReturnsFirstHandlerResponse(): void
    {
        $dispatcher = CommandBusDispatcher::instance();

        $this->assertCount(0, $this->spyHandler->commands());
        $this->assertCount(0, $this->sluttyHandler->commands());

        $dispatcher->dispatch(new FakeCommand(), UserMother::random());

        $this->assertCount(1, $this->spyHandler->commands());
        $this->assertCount(0, $this->sluttyHandler->commands());
    }

    public function testSubscribeNotAddSameSubscriberTwice(): void
    {
        $anotherSpy = new SluttyCommandHandler();

        $dispatcher = CommandBusDispatcher::instance();

        $this->assertCount(0, $this->spyHandler->commands());
        $this->assertCount(0, $anotherSpy->commands());

        $dispatcher->dispatch(new FakeCommand(), UserMother::random());

        $this->assertCount(1, $this->spyHandler->commands());
        $this->assertCount(0, $anotherSpy->commands());
    }
}

class FakeCommand implements Command {}
