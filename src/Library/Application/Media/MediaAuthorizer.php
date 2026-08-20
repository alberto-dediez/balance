<?php

declare(strict_types=1);

namespace App\Library\Application\Media;

use App\Library\Domain\Model\Media\Media;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;

final class MediaAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        if ($subject->isAccessibleOutsideAccount()) {
            return true;
        }

        return $this->belongsToUserAccount($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$this->belongsToUserAccount($subject, $user)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->userHasRole($user, Role::Owner)) {
            return true;
        }

        return $subject->uploadedBy() instanceof UserId && $subject->uploadedBy()->equals($user->id());
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canAdmin(mixed $subject, User $user): bool
    {
        if (!$this->belongsToUserAccount($subject, $user)) {
            return false;
        }

        return $this->isAdmin($user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Media;
    }

    private function isAdmin(User $user): bool
    {
        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }

        return $this->userHasRole($user, Role::Admin);
    }

    private function belongsToUserAccount(Media $media, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        if (!$media->accountId() instanceof AccountId) {
            return !$currentAccountId instanceof AccountId;
        }

        return $currentAccountId instanceof AccountId && $media->accountId()->equals($currentAccountId);
    }
}
