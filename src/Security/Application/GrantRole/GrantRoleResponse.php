<?php

namespace App\Security\Application\GrantRole;

use App\Security\Domain\Model\User\UserId;

class GrantRoleResponse
{
    public function __construct(public UserId $userId, public string $username, public array $roles)
    {
        
    }
}