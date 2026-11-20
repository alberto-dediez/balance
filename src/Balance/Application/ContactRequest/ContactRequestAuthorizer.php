<?php

declare(strict_types=1);

namespace App\Balance\Application\ContactRequest;

use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ContactRequestAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->isAdministrator($user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->isAdministrator($user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->isAdministrator($user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return false;
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ContactRequest;
    }

    private function isAdministrator(User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }
}
