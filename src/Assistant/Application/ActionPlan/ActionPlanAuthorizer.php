<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ActionPlanAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->isAdmin($user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->isAdmin($user) && $subject instanceof ActionPlan && $subject->isOf($user->id());
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canAdmin(mixed $subject, User $user): bool
    {
        return $this->isAdmin($user);
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ActionPlan;
    }

    private function isAdmin(User $user): bool
    {
        return $this->userHasRole($user, Role::Admin) || $this->userHasRole($user, Role::SuperAdmin);
    }
}
