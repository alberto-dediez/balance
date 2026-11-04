<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\LocalizedCommand;
use App\Shared\Application\CommandBus\LocalizedCommandHandler;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class LocalizedCommandHandlerTest extends TestCase
{
    private SluttyCommandHandler $captureHandler;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->captureHandler = new SluttyCommandHandler();
        LocaleGetter::setLocale(null);
    }

    public function testIsHandledForLocalizedCommand(): void
    {
        $sut = $this->createSUT();
        $command = new LocalizedCommand(new FakeLocalizedInnerCommand(), Locale::Spanish);

        $this->assertTrue($sut->isHandled($command));
    }

    public function testIsNotHandledForOtherCommands(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isHandled(new FakeLocalizedInnerCommand()));
    }

    public function testDispatchesInnerCommandToCommandBus(): void
    {
        $sut = $this->createSUT();
        $user = UserMother::adminUser();
        $inner = new FakeLocalizedInnerCommand();
        $command = new LocalizedCommand($inner, Locale::English);

        $sut->handle($command, $user);

        $commands = $this->captureHandler->commands();
        $this->assertCount(1, $commands);
        $this->assertSame($inner, $commands[0]['command']);
    }

    public function testSetsLocaleBeforeDispatch(): void
    {
        $localeOnDispatch = null;

        CommandBusDispatcher::instance()->reset();
        new LocaleCapturingHandler(function () use (&$localeOnDispatch) {
            $localeOnDispatch = LocaleGetter::locale();
        });

        LocaleGetter::setLocale(Locale::Spanish);
        $sut = $this->createSUT();
        $sut->handle(new LocalizedCommand(new FakeLocalizedInnerCommand(), Locale::English), UserMother::adminUser());

        $this->assertEquals(Locale::English, $localeOnDispatch);
    }

    public function testRestoresPreviousLocaleAfterDispatch(): void
    {
        $sut = $this->createSUT();

        LocaleGetter::setLocale(Locale::Spanish);
        $sut->handle(new LocalizedCommand(new FakeLocalizedInnerCommand(), Locale::English), UserMother::adminUser());

        $this->assertEquals(Locale::Spanish, LocaleGetter::locale());
    }

    public function testRestoresPreviousLocaleEvenOnException(): void
    {
        CommandBusDispatcher::instance()->reset();
        new ThrowingLocaleCommandHandler();

        $sut = $this->createSUT();
        LocaleGetter::setLocale(Locale::Spanish);

        try {
            $sut->handle(new LocalizedCommand(new FakeLocalizedInnerCommand(), Locale::English), UserMother::adminUser());
        } catch (\RuntimeException) {
        }

        $this->assertEquals(Locale::Spanish, LocaleGetter::locale());
    }

    private function createSUT(): LocalizedCommandHandler
    {
        return new LocalizedCommandHandler();
    }
}
