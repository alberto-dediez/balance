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
        return $this->belongsToUserAccount($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return true;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if (!$this->belongsToUserAccount($subject, $user)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(Role::Owner)) {
            return true;
        }

        return $subject->uploadedBy() instanceof UserId && $subject->uploadedBy()->equals($user->id());
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Media;
    }

    private function isAdmin(User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $user->hasRole(Role::Admin);
    }

    /**
     * The library is always scoped to the account of the user, whatever its role.
     * Users without account only reach the media not belonging to any account.
     */
    private function belongsToUserAccount(Media $media, User $user): bool
    {
        if (!$media->accountId() instanceof AccountId) {
            return !$user->accountId() instanceof AccountId;
        }

        return $user->accountId() instanceof AccountId && $media->accountId()->equals($user->accountId());
    }
}
