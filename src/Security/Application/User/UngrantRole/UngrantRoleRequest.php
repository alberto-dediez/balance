<?php

namespace App\Security\Application\User\UngrantRole;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;

class UngrantRoleRequest
{
    public function __construct(public UserId $userId, public Role $role)
    {

    }
}