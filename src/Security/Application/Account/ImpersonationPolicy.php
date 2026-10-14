<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ImpersonationPolicy
{
    public function __construct(private readonly ?AccountRepository $repository = null) {}

    public function canImpersonateAccounts(User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->consultantAccount($user) instanceof Account;
    }

    public function canImpersonate(Account $account, User $user): bool
    {
        if (!$account->isActive() || $account->isRemoved()) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        $consultantAccount = $this->consultantAccount($user);

        if (!$consultantAccount instanceof Account) {
            return false;
        }

        $assignedConsultantAccountId = $account->consultantAccountId();

        return $assignedConsultantAccountId instanceof AccountId && $assignedConsultantAccountId->equals($consultantAccount->id());
    }

    public function impersonableAccountsFilter(User $user, ?string $name = null, int $limit = 10): ?AccountFilter
    {
        if ($this->isAdmin($user)) {
            return new AccountFilter(name: $name, active: true, limit: $limit);
        }

        $consultantAccount = $this->consultantAccount($user);

        if (!$consultantAccount instanceof Account) {
            return null;
        }

        return new AccountFilter(name: $name, active: true, consultantAccountId: $consultantAccount->id(), limit: $limit);
    }

    private function isAdmin(User $user): bool
    {
        return $user->hasRole(Role::Admin) || $user->hasRole(Role::SuperAdmin);
    }

    private function consultantAccount(User $user): ?Account
    {
        if (!$this->repository instanceof AccountRepository || !$user->hasRole(Role::Owner)) {
            return null;
        }

        $accountId = $user->accountId();

        if (!$accountId instanceof AccountId) {
            return null;
        }

        $account = $this->repository->find($accountId);

        if (!$account instanceof Account || !$account->isConsultant()) {
            return null;
        }

        return $account->isActive() && !$account->isRemoved() ? $account : null;
    }
}
