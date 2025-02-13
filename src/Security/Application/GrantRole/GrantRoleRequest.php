<?php

namespace App\Security\Application\GrantRole;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;

class GrantRoleRequest
{
    public function __construct(public UserId $userId, public Role $role)
    {

    }
}