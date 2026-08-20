<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CurrentRoles;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

class StubCurrentRolesProvider implements CurrentRolesProvider
{
    /**
     * @param Role[]|null $roles
     */
    public function __construct(private readonly ?array $roles = null) {}

    public function currentRoles(User $user): ?array
    {
        return $this->roles;
    }

    public function currentLoggedUserRoles(): ?array
    {
        return $this->roles;
    }
}
