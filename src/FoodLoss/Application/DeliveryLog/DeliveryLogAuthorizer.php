<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\Security\Application\AbstractAuthorizer;
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
        if (!($user->hasRole(Role::Owner) || $user->hasRole(Role::User))) {
            return false;
        }

        if (null === $user->accountId()) {
            return false;
        }

        assert($subject instanceof DeliveryLog);

        return null !== $subject->accountId() && $subject->accountId()->equals($user->accountId());
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof DeliveryLog;
    }
}
