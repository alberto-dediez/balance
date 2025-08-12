<?php

namespace App\Security\Application\User\RecoverPassword;

use App\Security\Domain\Model\User\UserId;

class RecoverPasswordResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}