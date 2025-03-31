<?php

namespace App\Security\Application\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Application\AbstractAuthorizer;

final class UserAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin) || $user->hasRole(Role::Owner));
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $user->accountId()->equals($subject->accountId()))
            || $subject->id()->equals($user->id())
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin) || $user->hasRole(Role::Owner));
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $user->accountId()->equals($subject->accountId()))
            || $subject->id()->equals($user->id())
        ;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin) 
            || $user->hasRole(Role::SuperAdmin) 
            || ($user->hasRole(Role::Owner) && $user->accountId()->equals($subject->accountId()))
            || $subject->id()->equals($user->id())
        ;
    }  
    
    public function supports(mixed $subject): bool
    {
        return $subject instanceof User;
    }
}