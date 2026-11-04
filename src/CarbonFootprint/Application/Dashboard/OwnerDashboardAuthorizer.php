<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardRequest;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class OwnerDashboardAuthorizer extends AbstractAuthorizer
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
        assert($subject instanceof GetOwnerDashboardRequest);

        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && $currentAccountId->equals($subject->accountId);
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
        return $subject instanceof GetOwnerDashboardRequest;
    }
}
