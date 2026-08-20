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
        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }
        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }

        return $this->userHasRole($user, Role::Owner) && $isFromSameAccount;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Admin)
            || $this->userHasRole($user, Role::SuperAdmin)
            || ($this->userHasRole($user, Role::Owner) && ($currentAccountId instanceof AccountId && $currentAccountId->equals($subject->accountId())))
            || $subject->id()->equals($user->id())
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canImpersonate(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin)
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
        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }

        return $this->userHasRole($user, Role::SuperAdmin);
    }

    public function canChangeRole(User $subject, Role $role, User $user): bool
    {
        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }
        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Owner)
        && $currentAccountId instanceof AccountId
        && $subject->accountId() instanceof AccountId
        && $currentAccountId->equals($subject->accountId())
        && (
            in_array($role, Role::casesForOwners())
            || Role::Provider === $role
        );
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof User;
    }
}
