<?php

namespace App\Security\Application\User\Remove;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveUserResponse implements CommandResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}