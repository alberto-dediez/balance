<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\Alert\Alert;

final class AlertAuthorizer extends AbstractAuthorizer
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
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        if (!$subject instanceof Alert || null == $subject->accountId()) {
            return false;
        }

        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && $user->accountId()->equals($subject->accountId());
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Alert;
    }
}
