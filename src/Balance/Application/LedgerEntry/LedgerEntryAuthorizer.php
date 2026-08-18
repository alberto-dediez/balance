<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class LedgerEntryAuthorizer extends AbstractAuthorizer
{
    protected function canRegister(mixed $subject, User $user): bool
    {
        return $user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return true;
        }

        assert($subject instanceof LedgerEntry);

        return $this->isOwnAccountEntry($subject, $user);
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
        return $subject instanceof LedgerEntry;
    }

    private function isOwnAccountEntry(LedgerEntry $ledgerEntry, User $user): bool
    {
        $accountId = $user->accountId();

        return $user->hasRole(Role::Owner)
            && $accountId instanceof AccountId
            && $ledgerEntry->belongsTo(LedgerBeneficiary::Account, (string) $accountId);
    }
}
