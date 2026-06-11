<?php

declare(strict_types=1);

namespace App\Security\Application\SignatureRequest;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class SignatureRequestAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user) || null !== $user->accountId();
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        assert($subject instanceof SignatureRequest);

        if (!$subject->isPending()) {
            return false;
        }

        if (null !== $subject->requestedBy() && $subject->requestedBy()->equals($user->id())) {
            return true;
        }

        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof SignatureRequest;
    }
}
