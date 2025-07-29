<?php

namespace App\Property\Application\Destination;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Property\Domain\Model\Destination\Destination;
use App\Security\Application\AbstractAuthorizer;

final class DestinationAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
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
        return $subject instanceof Destination;
    }
}