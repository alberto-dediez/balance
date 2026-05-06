<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCause;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class SurplusCauseAuthorizer extends AbstractAuthorizer
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
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof SurplusCause;
    }
}
