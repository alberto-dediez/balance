<?php

declare(strict_types=1);

namespace App\Property\Application\Property;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\Property\Property;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class PropertyAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?CurrentImpersonationProvider $currentImpersonationProvider = null,
        ?ConsultantSupervisionPolicy $consultantSupervisionPolicy = null,
        ?AccountFeaturesPolicy $accountFeaturesPolicy = null,
        private readonly ?DistributedPropertiesPolicy $distributedPropertiesPolicy = null,
        private readonly ?DistributorPolicy $distributorPolicy = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider, $currentImpersonationProvider, $consultantSupervisionPolicy, $accountFeaturesPolicy);
    }

    public function isOwnerOfCurrentAccount(User $user, AccountId $accountId): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        return $this->userHasRole($user, Role::Owner)
            && $currentAccountId instanceof AccountId
            && $currentAccountId->equals($accountId);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);

        if ($this->userHasRole($user, Role::Owner)
        && null != $currentAccountId
        && null != $subject->accountId()
        && $currentAccountId->equals($subject->accountId())
        && $this->hasPropertyFeature($user)) {
            return true;
        }

        return $this->userHasRole($user, Role::Admin);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user) || $this->isDistributedToCurrentAccount($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user) || $this->isActiveDistributorAccount($subject, $user);
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    protected function canExport(mixed $subject, User $user): bool
    {
        return $this->canRegister($subject, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof Property;
    }

    private function isDistributedToCurrentAccount(Property $property, User $user): bool
    {
        if (!$this->distributedPropertiesPolicy instanceof DistributedPropertiesPolicy || !$this->userHasRole($user, Role::Owner)) {
            return false;
        }

        $currentAccountId = $this->currentAccountId($user);

        return $currentAccountId instanceof AccountId && $this->distributedPropertiesPolicy->distributes($currentAccountId, $property);
    }

    private function isActiveDistributorAccount(Property $property, User $user): bool
    {
        $accountId = $property->accountId();

        if (!$this->distributorPolicy instanceof DistributorPolicy || !$accountId instanceof AccountId) {
            return false;
        }

        return $this->isOwnerOfCurrentAccount($user, $accountId) && $this->distributorPolicy->isActiveDistributor($accountId);
    }

    private function hasPropertyFeature(User $user): bool
    {
        return $this->currentAccountHasFeature($user, AccountFeature::Events)
            || $this->currentAccountHasFeature($user, AccountFeature::OrganizationalCarbonFootprint)
            || $this->currentAccountHasFeature($user, AccountFeature::PropertyTripCalculators);
    }
}
