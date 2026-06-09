<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertCheckQueue;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueue;

final class AlertCheckQueueAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return true;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof AlertCheckQueue;
    }
}
