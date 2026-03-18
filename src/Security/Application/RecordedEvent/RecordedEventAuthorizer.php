<?php

declare(strict_types=1);

namespace App\Security\Application\RecordedEvent;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class RecordedEventAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return true;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $user->hasRole(Role::Admin);
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
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof RecordedEvent;
    }
}
