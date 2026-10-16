<?php

declare(strict_types=1);

namespace App\Security\Application;

use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

abstract class AbstractAuthorizer implements Authorizer
{
    public function __construct(
        private readonly ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        private readonly ?CurrentRolesProvider $currentRolesProvider = null,
        private readonly ?CurrentImpersonationProvider $currentImpersonationProvider = null,
        private readonly ?ConsultantSupervisionPolicy $consultantSupervisionPolicy = null,
    ) {}

    protected function currentAccountId(User $user): ?AccountId
    {
        return $this->currentAccountIdProvider?->currentAccountId() ?? $user->accountId();
    }

    protected function isImpersonating(User $user): bool
    {
        return true === $this->currentImpersonationProvider?->isImpersonating($user);
    }

    protected function userHasRole(User $user, Role $role): bool
    {
        $currentRoles = $this->currentRolesProvider?->currentRoles($user);

        if (null === $currentRoles) {
            return $user->hasRole($role);
        }

        return in_array($role, $currentRoles, true);
    }

    public function canDoThat(mixed $subject, Action $action, User $user): bool
    {
        if (!$this->supports($subject)) {
            return false;
        }

        return match ($action) {
            Action::Register => $this->canRegister($subject, $user),
            Action::View => $this->canView($subject, $user),
            Action::List => $this->canList($subject, $user),
            Action::Edit => $this->canEdit($subject, $user),
            Action::Remove => $this->canRemove($subject, $user),
            Action::Admin => $this->canAdmin($subject, $user),
            Action::Autocomplete => $this->canAutocomplete($subject, $user),
            Action::Import => $this->canImport($subject, $user),
            Action::Export => $this->canExport($subject, $user),
            Action::History => $this->canHistory($subject, $user),
            Action::Impersonate => $this->canImpersonate($subject, $user),
            Action::Supervise => $this->canSupervise($subject, $user),
        };
    }

    public function denyUnlessCanDoThat(mixed $subject, Action $action, User $user): void
    {
        if (!$this->canDoThat($subject, $action, $user)) {
            throw new UnauthorizedActionException($subject, $action);
        }
    }

    protected function canAdmin(mixed $subject, User $user): bool
    {
        if ($this->userHasRole($user, Role::Admin)) {
            return true;
        }

        return $this->userHasRole($user, Role::SuperAdmin);
    }

    protected function canAutocomplete(mixed $subject, User $user): bool
    {
        return true;
    }

    protected function canHistory(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canImpersonate(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canSupervise(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function supervisesAccount(?AccountId $accountId, User $user): bool
    {
        if (!$accountId instanceof AccountId || !$this->consultantSupervisionPolicy instanceof ConsultantSupervisionPolicy) {
            return false;
        }

        $currentAccountId = $this->currentAccountId($user);

        if (!$currentAccountId instanceof AccountId || !$this->userHasRole($user, Role::Owner)) {
            return false;
        }

        return $this->consultantSupervisionPolicy->supervises($currentAccountId, $accountId);
    }

    /**
     * @return list<AccountId>|null
     */
    public function supervisedAccountIds(User $user): ?array
    {
        if (!$this->consultantSupervisionPolicy instanceof ConsultantSupervisionPolicy || !$this->userHasRole($user, Role::Owner)) {
            return null;
        }

        $currentAccountId = $this->currentAccountId($user);

        if (!$currentAccountId instanceof AccountId || !$this->consultantSupervisionPolicy->isActiveConsultant($currentAccountId)) {
            return null;
        }

        return $this->consultantSupervisionPolicy->supervisedAccountIds($currentAccountId);
    }

    protected function canImport(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return false;
    }

    abstract protected function canRegister(mixed $subject, User $user): bool;

    abstract protected function canView(mixed $subject, User $user): bool;

    abstract protected function canList(mixed $subject, User $user): bool;

    abstract protected function canEdit(mixed $subject, User $user): bool;

    abstract protected function canRemove(mixed $subject, User $user): bool;

    abstract public function supports(mixed $subject): bool;
}
