<?php

namespace App\Waste\Application\Container;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Application\AbstractAuthorizer;
use App\Waste\Domain\Model\Container\Container;

final class ContainerAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }
        
        return $user->hasRole(Role::Owner) 
            && $user->accountId()->equals($subject->accountId())
        ;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }

        return 
            $user->accountId()->equals($subject->accountId())
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId() || false == $user->accountId()->equals($subject->accountId())) {
            return false;
        }
        
        return true;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }  
    
    public function supports(mixed $subject): bool
    {
        return $subject instanceof Container;
    }
}