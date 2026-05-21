<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Surplus;

use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class SurplusAuthorizer extends AbstractAuthorizer
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

        assert($subject instanceof Surplus);

        return $subject->accountId()->equals($user->accountId());
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
        return $subject instanceof Surplus;
    }
}
