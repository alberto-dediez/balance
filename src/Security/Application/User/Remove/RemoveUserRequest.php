<?php

namespace App\Security\Application\User\Remove;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class RemoveUserRequest implements Command
{
    public function __construct(public UserId $userId)
    {

    }
}