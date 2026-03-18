<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Security\Domain\Model\User\User;

trait CommandInvokeHandlerTrait
{
    public function handle(Command $command, User $user): ?CommandResponse
    {
        return $this->__invoke($command, $user);
    }
}
