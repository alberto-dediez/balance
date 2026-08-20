<?php

declare(strict_types=1);

namespace App\DataExchange\Application\ImportationProfile;

use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfile;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ImportationProfileAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }

        return $this->userHasRole($user, Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    private function haveAccess(mixed $subject, User $user): bool
    {
        /* @var ImportationProfile $subject */
        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }
        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Owner)
            && $subject->accountId() instanceof AccountId
            && $currentAccountId instanceof AccountId
            && $subject->accountId()->equals($currentAccountId);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ImportationProfile;
    }
}
