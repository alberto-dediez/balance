<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentImpersonation\CurrentImpersonationProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class DistributorAssignmentAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?CurrentImpersonationProvider $currentImpersonationProvider = null,
        ?ConsultantSupervisionPolicy $consultantSupervisionPolicy = null,
        ?AccountFeaturesPolicy $accountFeaturesPolicy = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider, $currentImpersonationProvider, $consultantSupervisionPolicy, $accountFeaturesPolicy);
    }

    public function isAdmin(User $user): bool
    {
        return $this->userHasRole($user, Role::Admin) || $this->userHasRole($user, Role::SuperAdmin);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canAdmin($subject, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canRemove($subject, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->userHasRole($user, Role::Owner) && $this->currentAccountId($user) instanceof AccountId;
    }

    protected function canEdit(mixed $subject, User $user): bool
    {
        return false;
    }

    protected function canRemove(mixed $subject, User $user): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $currentAccountId = $this->currentAccountId($user);

        if (!$currentAccountId instanceof AccountId || !$this->userHasRole($user, Role::Owner)) {
            return false;
        }

        if (!$subject->isForProperty()) {
            $accountId = $subject->accountId();

            return $accountId instanceof AccountId && $currentAccountId->equals($accountId);
        }

        $propertyId = $subject->propertyId();
        $property = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null;

        if (!$property instanceof Property) {
            return true;
        }

        $propertyAccountId = $property->accountId();

        return $propertyAccountId instanceof AccountId && $currentAccountId->equals($propertyAccountId);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof DistributorAssignment;
    }
}
