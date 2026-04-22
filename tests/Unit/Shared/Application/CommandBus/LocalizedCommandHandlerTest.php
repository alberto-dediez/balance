<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\LocalizedCommand;
use App\Shared\Application\CommandBus\LocalizedCommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class LocalizedCommandHandlerTest extends TestCase
{
    private LocaleCapturingHandler $innerHandler;
    private User $user;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        LocaleGetter::setLocale(Locale::Spanish);

        $this->innerHandler = new LocaleCapturingHandler();
        $this->user = UserMother::adminUser();

        $sut = new LocalizedCommandHandler();
        $sut->register();
    }

    public function testIsHandledForLocalizedCommand(): void
    {
        $sut = new LocalizedCommandHandler();

        $this->assertTrue($sut->isHandled(new LocalizedCommand(new LocalizedFakeCommand(), Locale::English)));
    }

    public function testIsNotHandledForOtherCommands(): void
    {
        $sut = new LocalizedCommandHandler();

        $this->assertFalse($sut->isHandled(new LocalizedFakeCommand()));
    }

    public function testSetsRequestedLocaleWhenDispatchingInnerCommand(): void
    {
        CommandBusDispatcher::instance()->dispatch(
            new LocalizedCommand(new LocalizedFakeCommand(), Locale::English),
            $this->user,
        );

        $this->assertSame(Locale::English, $this->innerHandler->capturedLocale());
    }

    public function testRestoresPreviousLocaleAfterDispatch(): void
    {
        LocaleGetter::setLocale(Locale::French);

        CommandBusDispatcher::instance()->dispatch(
            new LocalizedCommand(new LocalizedFakeCommand(), Locale::German),
            $this->user,
        );

        $this->assertSame(Locale::French, LocaleGetter::locale());
    }

    public function testRestoresPreviousLocaleWhenInnerCommandThrows(): void
    {
        LocaleGetter::setLocale(Locale::Italian);

        $this->innerHandler->throwOnNextCall();

        $this->expectException(\RuntimeException::class);

        try {
            CommandBusDispatcher::instance()->dispatch(
                new LocalizedCommand(new LocalizedFakeCommand(), Locale::Turkish),
                $this->user,
            );
        } finally {
            $this->assertSame(Locale::Italian, LocaleGetter::locale());
        }
    }
}

class LocalizedFakeCommand implements Command {}

class LocaleCapturingHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    private ?Locale $capturedLocale = null;
    private bool $shouldThrow = false;

    public function __construct()
    {
        $this->register();
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LocalizedFakeCommand;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        $this->capturedLocale = LocaleGetter::locale();

        if ($this->shouldThrow) {
            throw new \RuntimeException('inner command failure');
        }

        return null;
    }

    public function capturedLocale(): ?Locale
    {
        return $this->capturedLocale;
    }

    public function throwOnNextCall(): void
    {
        $this->shouldThrow = true;
    }
}
