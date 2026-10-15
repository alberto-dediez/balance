<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRate;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ExchangeRateAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canImport(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canImpersonate(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ExchangeRate;
    }
}
