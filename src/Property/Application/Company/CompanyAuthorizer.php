<?php

declare(strict_types=1);

namespace App\Property\Application\Company;

use App\Property\Domain\Model\Company\Company;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class CompanyAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Owner)
        && null != $user->accountId()
        && null != $subject->accountId()
        && $user->accountId()->equals($subject->accountId())) {
            return true;
        }

        return $user->hasRole(Role::Admin) || $user->hasRole(Role::SuperAdmin);
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
        return $subject instanceof Company;
    }
}
