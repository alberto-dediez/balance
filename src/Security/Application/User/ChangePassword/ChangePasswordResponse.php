<?php

namespace App\Security\Application\User\ChangePassword;

use App\Security\Domain\Model\User\UserId;

class ChangePasswordResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}