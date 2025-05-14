<?php

namespace App\Waste\Application\WasteManager;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Security\Application\AbstractAuthorizer;

final class WasteManagerAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return (
                $user->hasRole(Role::Owner) 
                && null != $user->accountId()
                && null != $subject->accountId()
                && $user->accountId()->equals($subject->accountId())
            )
        ;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
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
        return $subject instanceof WasteManager;
    }
}