<?php

declare(strict_types=1);

namespace App\Security\Application\Branding;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class BrandingAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
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
        if ($user->hasRole(Role::Admin)) {
            return true;
        }
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $user->hasRole(Role::Owner) && ($user->accountId() instanceof AccountId && $subject->accountId()->equals($user->accountId()));
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function canEditTheme(Branding $branding, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::SuperAdmin);
    }

    public function denyUnlessCanEditTheme(Branding $branding, User $user): void
    {
        if (!$this->canEditTheme($branding, $user)) {
            throw new UnauthorizedActionException($branding, Action::Edit);
        }
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Branding;
    }
}
