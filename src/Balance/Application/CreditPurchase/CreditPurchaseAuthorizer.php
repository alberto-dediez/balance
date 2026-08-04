<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class CreditPurchaseAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        assert($subject instanceof CreditPurchase);

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

        return $this->canRegister($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof CreditPurchase;
    }
}
