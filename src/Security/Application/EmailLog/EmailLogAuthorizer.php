<?php

declare(strict_types=1);

namespace App\Security\Application\EmailLog;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class EmailLogAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin);
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin);
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
        return $subject instanceof EmailLog;
    }
}
