<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess;

use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ProtocolAccessAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        assert($subject instanceof ProtocolAccess);

        if (!$user->accountId() instanceof AccountId) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        if (null === $subjectAccountId) {
            return false;
        }

        return $user->hasRole(Role::Owner)
            && $user->accountId()->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ProtocolAccess;
    }
}
