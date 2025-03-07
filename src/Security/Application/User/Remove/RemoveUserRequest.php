<?php

namespace App\Security\Application\User\Remove;

use App\Security\Domain\Model\User\UserId;

class RemoveUserRequest
{
    public function __construct(public UserId $userId)
    {

    }
}