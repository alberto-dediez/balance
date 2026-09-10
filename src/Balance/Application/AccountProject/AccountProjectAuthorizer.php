<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProject;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class AccountProjectAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user) || $this->isOwnAccount($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user) || $this->isOwnAccount($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->isOwnAccount($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->isOwnAccount($subject, $user);
    }

    private function isOwnAccount(mixed $subject, User $user): bool
    {
        assert($subject instanceof AccountProject);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && null !== $subjectAccountId
            && $currentAccountId->equals($subjectAccountId);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof AccountProject;
    }
}
