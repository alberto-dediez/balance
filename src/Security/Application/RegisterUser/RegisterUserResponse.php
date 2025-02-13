<?php

namespace App\Security\Application\RegisterUser;

use App\Security\Domain\Model\User\UserId;

class RegisterUserResponse
{
    public function __construct(public UserId $userId, public string $username)
    {
        
    }
}