<?php

namespace App\Security\Application\User\ChangePassword;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class ChangePasswordResponse implements CommandResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}