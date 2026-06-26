<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ElectricityMarketerAuthorizer extends AbstractAuthorizer
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

    protected function canImport(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ElectricityMarketer;
    }
}
