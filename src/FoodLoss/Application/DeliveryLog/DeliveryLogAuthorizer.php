<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class DeliveryLogAuthorizer extends AbstractAuthorizer
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
        if (!$user->hasRole(Role::Owner) && !$user->hasRole(Role::User)) {
            return false;
        }

        if (!$user->accountId() instanceof AccountId) {
            return false;
        }

        assert($subject instanceof DeliveryLog);

        return $subject->accountId() instanceof AccountId && $subject->accountId()->equals($user->accountId());
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && $subject instanceof DeliveryLog
            && $subject->accountId() instanceof AccountId
            && $subject->accountId()->equals($user->accountId());
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof DeliveryLog;
    }
}
