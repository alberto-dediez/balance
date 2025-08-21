<?php

namespace App\Tests\Unit\Shared\Application\CommandBus;

use PHPUnit\Framework\TestCase;
use App\Shared\Application\CommandBus\Command;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Shared\Application\CommandBus\CommandBusDispatcher;

class CommandBusDispatcherTest extends TestCase
{
    private $spyHandler;
    private $sluttyHandler;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();

        $this->spyHandler = new SluttyCommandHandler();
        $this->sluttyHandler = new VeryPickyCommandHandler();      
    }

    public function testDispatchReturnsFirstHandlerResponse()
    {
        $dispatcher = CommandBusDispatcher::instance();

        $this->assertCount(0, $this->spyHandler->commands());
        $this->assertCount(0, $this->sluttyHandler->commands());  
        
        $dispatcher->dispatch(new FakeCommand(), UserMother::random());

        $this->assertCount(1, $this->spyHandler->commands());
        $this->assertCount(0, $this->sluttyHandler->commands());
    }

    public function testSubscribeNotAddSameSubscriberTwice()
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

class FakeCommand implements Command
{
    
}