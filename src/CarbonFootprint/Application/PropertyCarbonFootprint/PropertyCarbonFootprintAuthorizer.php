<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class PropertyCarbonFootprintAuthorizer extends AbstractAuthorizer
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
        assert($subject instanceof PropertyCarbonFootprint);

        $currentAccountId = $this->currentAccountId($user);
        $subjectAccountId = $subject->accountId();

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && null !== $subjectAccountId
            && $currentAccountId->equals($subjectAccountId);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        if (!$this->userHasRole($user, Role::Owner) || !$currentAccountId instanceof AccountId) {
            return false;
        }

        if (!$subject instanceof PropertyCarbonFootprint) {
            return false;
        }

        $subjectAccountId = $subject->accountId();

        return null !== $subjectAccountId && $currentAccountId->equals($subjectAccountId);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user) || $this->userHasRole($user, Role::Admin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof PropertyCarbonFootprint;
    }
}
