<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class NotificationAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if ($subject instanceof Notification
            && null !== $subject->recipientId()
            && $subject->recipientId()->equals($user->id())
        ) {
            return true;
        }

        return $this->canAdmin($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        if ($subject instanceof Notification
            && null !== $subject->recipientId()
            && $subject->recipientId()->equals($user->id())
        ) {
            return true;
        }

        return $this->userHasRole($user, Role::SuperAdmin) || $this->userHasRole($user, Role::Admin);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Notification;
    }
}
