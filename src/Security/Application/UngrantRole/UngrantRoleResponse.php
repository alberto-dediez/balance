<?php

namespace App\Security\Application\UngrantRole;

use App\Security\Domain\Model\User\UserId;

class UngrantRoleResponse
{
    public function __construct(public UserId $userId, public string $username, public array $roles)
    {
        
    }
}