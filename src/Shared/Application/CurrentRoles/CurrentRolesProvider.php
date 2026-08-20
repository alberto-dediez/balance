<?php

declare(strict_types=1);

namespace App\Shared\Application\CurrentRoles;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

interface CurrentRolesProvider
{
    /**
     * @return Role[]|null
     */
    public function currentRoles(User $user): ?array;

    /**
     * @return Role[]|null
     */
    public function currentLoggedUserRoles(): ?array;
}
