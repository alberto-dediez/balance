<?php

namespace App\Security\Application\User\GrantRole;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class GrantRoleResponse implements CommandResponse
{
    /**
     * @param Role[] $roles
     */
    public function __construct(public UserId $userId, public string $username, public array $roles)
    {

    }
}
