<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ProjectContributionAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        assert($subject instanceof ProjectContribution);

        $subjectAccountId = $subject->accountId();

        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && null !== $subjectAccountId
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        assert($subject instanceof ProjectContribution);

        $subjectAccountId = $subject->accountId();

        return $user->accountId() instanceof AccountId
            && null !== $subjectAccountId
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ProjectContribution;
    }
}
