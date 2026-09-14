<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ChangelogEntryAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return $this->userHasRole($user, Role::SuperAdmin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof ChangelogEntry;
    }
}
