<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ActivityAuthorizer extends AbstractAuthorizer
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
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin) || $this->userHasRole($user, Role::Owner) || $this->userHasRole($user, Role::Provider);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$subject instanceof Activity) {
            return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin)
                || $this->userHasRole($user, Role::Owner) || $this->userHasRole($user, Role::Provider);
        }

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return ($this->userHasRole($user, Role::Owner) || $this->userHasRole($user, Role::Provider))
            && $currentAccountId instanceof AccountId
            && null !== $subjectAccountId
            && $currentAccountId->equals($subjectAccountId);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Activity;
    }
}
