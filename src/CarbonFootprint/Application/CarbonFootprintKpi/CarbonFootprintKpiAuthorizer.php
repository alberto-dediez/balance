<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class CarbonFootprintKpiAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        assert($subject instanceof KpiSubject);

        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && null !== $subject->accountId
            && $currentAccountId->equals($subject->accountId);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::Owner)
            && $this->currentAccountId($user) instanceof AccountId;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof KpiSubject;
    }
}
