<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class AdminDashboardAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canList(mixed $subject, User $user): bool
    {
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
        return $subject instanceof GetAdminDashboardRequest;
    }
}
