<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ImpersonationPolicy
{
    public function canImpersonateAccounts(User $user): bool
    {
        return $user->hasRole(Role::Admin) || $user->hasRole(Role::SuperAdmin);
    }

    public function canImpersonate(Account $account, User $user): bool
    {
        if (!$this->canImpersonateAccounts($user)) {
            return false;
        }

        return $account->isActive() && !$account->isRemoved();
    }
}
