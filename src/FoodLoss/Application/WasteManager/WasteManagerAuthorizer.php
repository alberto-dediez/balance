<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\WasteManager;

use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class WasteManagerAuthorizer extends AbstractAuthorizer
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
        assert($subject instanceof WasteManager);

        $subjectAccountId = $subject->accountId();

        return $user->hasRole(Role::Owner)
            && $user->accountId() instanceof AccountId
            && $subjectAccountId instanceof AccountId
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$user->hasRole(Role::Owner) || !$user->accountId() instanceof AccountId) {
            return false;
        }

        if (!$subject instanceof WasteManager) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        return $subjectAccountId instanceof AccountId && $user->accountId()->equals($subjectAccountId);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof WasteManager;
    }
}
