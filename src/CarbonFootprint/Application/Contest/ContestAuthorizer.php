<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class ContestAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?AccountFeaturesPolicy $accountFeaturesPolicy = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider, null, null, $accountFeaturesPolicy);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return $this->canManage($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Contest;
    }

    private function canManage(Contest $contest, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);
        $accountId = $contest->accountId();

        return $this->userHasRole($user, Role::Owner)
            && null !== $currentAccountId
            && null !== $accountId
            && $currentAccountId->equals($accountId)
            && $this->currentAccountHasFeature($user, AccountFeature::Contests);
    }
}
