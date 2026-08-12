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
        // Being able to open the file has nothing to do with browsing it in the library:
        // the audience of the file is what opens the access, not the sharing.
        if ($subject->isAccessibleOutsideAccount()) {
            return true;
        }

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

    /**
     * The library is scoped to the account even for the administrators: an administrator
     * of another account is a stranger to this file.
     *
     * Publishing a file in the library of every account is an administration action:
     * left to any user it would turn the shared library into a channel to spread spam
     * or malware across the accounts.
     */
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
