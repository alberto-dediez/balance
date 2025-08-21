<?php

namespace App\Security\Application\User\RecoverPassword;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class RecoverPasswordResponse implements CommandResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}