<?php

namespace App\Security\Application\Account;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\Account\Account;
use App\Security\Application\AbstractAuthorizer;

final class AccountAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin));
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $subject->id()->equals($user->accountId()))
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin));
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $subject->id()->equals($user->accountId()))
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $subject->id()->equals($user->accountId()))
        ;
    }  
    
    public function supports(mixed $subject): bool
    {
        return $subject instanceof Account;
    }
}