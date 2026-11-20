<?php

declare(strict_types=1);

namespace App\Library\Application\Folder;

use App\Library\Domain\Model\Folder\Folder;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class FolderAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->belongsToUserAccount($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Folder;
    }

    private function canManage(Folder $folder, User $user): bool
    {
        if (!$this->belongsToUserAccount($folder, $user)) {
            return false;
        }

        if ($this->userHasRole($user, Role::SuperAdmin)) {
            return true;
        }

        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }

        return $this->userHasRole($user, Role::Owner);
    }

    private function belongsToUserAccount(Folder $folder, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        if (!$folder->accountId() instanceof AccountId) {
            return !$currentAccountId instanceof AccountId;
        }

        return $currentAccountId instanceof AccountId && $folder->accountId()->equals($currentAccountId);
    }
}
