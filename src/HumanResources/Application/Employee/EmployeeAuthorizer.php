<?php

declare(strict_types=1);

namespace App\HumanResources\Application\Employee;

use App\HumanResources\Domain\Model\Employee\Employee;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class EmployeeAuthorizer extends AbstractAuthorizer
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
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        assert($subject instanceof Employee);

        $subjectAccountId = $subject->accountId();

        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        if (!$user->hasRole(Role::Owner) || !$user->accountId() instanceof AccountId) {
            return false;
        }

        if (!$subject instanceof Employee) {
            return false;
        }

        return $user->accountId()->equals($subject->accountId());
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Employee;
    }
}
