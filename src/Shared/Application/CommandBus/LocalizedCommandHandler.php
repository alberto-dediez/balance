<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class LocalizedCommandHandler implements CommandHandler
{
    use RegisterHandlerTrait;

    public function isHandled(Command $command): bool
    {
        return $command instanceof LocalizedCommand;
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof LocalizedCommand);

        $previous = LocaleGetter::locale();
        LocaleGetter::setLocale($command->locale());

        try {
            return CommandBusDispatcher::instance()->dispatch($command->command(), $user);
        } finally {
            LocaleGetter::setLocale($previous);
        }
    }
}
