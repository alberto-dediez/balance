<?php

namespace App\Security\Application\User\UngrantRole;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class UngrantRoleRequest implements Command
{
    public function __construct(public UserId $userId, public Role $role)
    {

    }
}