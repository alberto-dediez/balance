<?php

declare(strict_types=1);

namespace App\DataExchange\Application\Importation;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ImportationAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        /** @var Importation $subject */
        if (!$subject->isExecuted() && !$subject->isInProgress()) {
            return false;
        }

        return $this->haveAccess($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        /** @var Importation $subject */
        if ($subject->isExecuted() || $subject->isInProgress()) {
            return false;
        }

        return $this->haveAccess($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->haveAccess($subject, $user);
    }

    private function haveAccess(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $user->hasRole(Role::Owner) && ($currentAccountId instanceof AccountId && $subject->accountId()->equals($currentAccountId));
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Importation;
    }
}
