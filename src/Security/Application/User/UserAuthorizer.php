<?php

namespace App\Security\Application\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Application\AbstractAuthorizer;

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
        $isFromSameAccount = null !== $subject->accountId() && null !== $user->accountId() && $user->accountId()->equals($subject->accountId());

        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin) || ($user->hasRole(Role::Owner) && $isFromSameAccount));
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin)
            || $user->hasRole(Role::SuperAdmin)
            || ($user->hasRole(Role::Owner) && (null !== $user->accountId() && $user->accountId()->equals($subject->accountId())))
            || $subject->id()->equals($user->id())
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin)
            || $user->hasRole(Role::SuperAdmin)
        ;
    }

    public function canChangeRole(User $subject, Role $role, User $user): bool
    {
        if (
            $user->hasRole(Role::SuperAdmin)
            || $user->hasRole(Role::Admin)
            || (
                $user->hasRole(Role::Owner)
                && null !== $user->accountId()
                && null !== $subject->accountId()
                && $user->accountId()->equals($subject->accountId())
                && in_array($role, Role::casesForOwners())
            )
        ) {
            return true;
        }

        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof User;
    }
}
