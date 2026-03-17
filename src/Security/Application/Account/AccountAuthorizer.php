<?php

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\Account\Account;
use App\Security\Application\AbstractAuthorizer;

final class AccountAuthorizer extends AbstractAuthorizer
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
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }
        return $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }
        return $user->hasRole(Role::Owner) && ($user->accountId() instanceof AccountId && $subject->id()->equals($user->accountId()));
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }  
    
    public function supports(mixed $subject): bool
    {
        return $subject instanceof Account;
    }
}