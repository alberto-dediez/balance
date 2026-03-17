<?php

namespace App\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;

interface CommandHandler
{
    public function isHandled(Command $command): bool;

    public function handle(Command $command, User $user): ?CommandResponse;

    public function register(): void;
}
