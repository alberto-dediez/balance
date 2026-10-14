<?php

declare(strict_types=1);

namespace App\Shared\Application\CurrentUser;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class CurrentUserContext
{
    public function __construct(
        private readonly ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        private readonly ?CurrentRolesProvider $currentRolesProvider = null,
    ) {}

    public function accountId(User $user): ?AccountId
    {
        return $this->currentAccountIdProvider?->currentAccountId() ?? $user->accountId();
    }

    public function hasRole(User $user, Role $role): bool
    {
        $currentRoles = $this->currentRolesProvider?->currentRoles($user);

        if (null === $currentRoles) {
            return $user->hasRole($role);
        }

        return in_array($role, $currentRoles, true);
    }
}
