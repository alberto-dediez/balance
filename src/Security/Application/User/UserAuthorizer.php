<?php

declare(strict_types=1);

namespace App\Security\Application\User;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class UserAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);
        $isFromSameAccount = null !== $subject->accountId() && $currentAccountId instanceof AccountId && $currentAccountId->equals($subject->accountId());
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::Owner) && $isFromSameAccount;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        return $user->hasRole(Role::Admin)
            || $user->hasRole(Role::SuperAdmin)
            || ($user->hasRole(Role::Owner) && ($currentAccountId instanceof AccountId && $currentAccountId->equals($subject->accountId())))
            || $subject->id()->equals($user->id())
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canImpersonate(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin)
            && $subject->isActive()
            && !$subject->id()->equals($user->id())
        ;
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::SuperAdmin);
    }

    public function canChangeRole(User $subject, Role $role, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $user->hasRole(Role::Owner)
        && $currentAccountId instanceof AccountId
        && $subject->accountId() instanceof AccountId
        && $currentAccountId->equals($subject->accountId())
        && in_array($role, Role::casesForOwners());
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof User;
    }
}
