<?php

namespace App\DataExchange\Application\Importation;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ImportationAuthorizer extends AbstractAuthorizer
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
        return ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin));
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin)
            || $user->hasRole(Role::SuperAdmin)
            || ($user->hasRole(Role::Owner) && (null !== $user->accountId() && $subject->id()->equals($user->accountId())))
        ;
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
