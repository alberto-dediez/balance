<?php

namespace App\Security\Application\RemoveUser;

use App\Security\Domain\Model\User\UserId;

class RemoveUserResponse
{
    public function __construct(public UserId $userId)
    {
        
    }
}