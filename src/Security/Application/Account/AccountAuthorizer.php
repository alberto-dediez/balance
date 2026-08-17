<?php

declare(strict_types=1);

namespace App\Security\Application\Account;

use App\Security\Application\AbstractAuthorizer;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

final class AccountAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        private readonly ImpersonationPolicy $impersonationPolicy = new ImpersonationPolicy(),
    ) {
        parent::__construct($currentAccountIdProvider);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canList($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        return $user->hasRole(Role::Admin);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }
        if ($user->hasRole(Role::SuperAdmin)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $user->hasRole(Role::Owner) && ($currentAccountId instanceof AccountId && $subject->id()->equals($currentAccountId));
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canEdit($subject, $user);
    }

    protected function canImpersonate(mixed $subject, User $user): bool
    {
        return $this->impersonationPolicy->canImpersonate($subject, $user);
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        if ($user->hasRole(Role::Admin)) {
            return true;
        }

        return $user->hasRole(Role::SuperAdmin);
    }

    public function canImpersonateAccounts(User $user): bool
    {
        return $this->impersonationPolicy->canImpersonateAccounts($user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Account;
    }
}
