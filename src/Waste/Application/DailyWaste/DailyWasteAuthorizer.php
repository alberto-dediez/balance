<?php

declare(strict_types=1);

namespace App\Waste\Application\DailyWaste;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;

final class DailyWasteAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        if (null == $user->accountId() || null == $subject->accountId()) {
            return false;
        }

        return $user->accountId()->equals($subject->accountId());
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if (null == $user->accountId()) {
            return false;
        }

        return
            $user->accountId()->equals($subject->accountId())
            && $user->hasRole(Role::Owner)
        ;
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if (null == $subject->accountId()) {
            return false;
        }

        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        if (null == $user->accountId() || false === $user->accountId()->equals($subject->accountId())) {
            return false;
        }

        return $user->hasRole(Role::Owner);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof DailyWaste;
    }
}
