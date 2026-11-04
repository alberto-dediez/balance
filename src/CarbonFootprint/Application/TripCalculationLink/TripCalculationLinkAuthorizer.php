<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\AbstractAuthorizer;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class TripCalculationLinkAuthorizer extends AbstractAuthorizer
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $propertyAuthorizer,
        ?CurrentAccountIdProvider $currentAccountIdProvider = null,
        ?CurrentRolesProvider $currentRolesProvider = null,
        ?AccountFeaturesPolicy $accountFeaturesPolicy = null,
    ) {
        parent::__construct($currentAccountIdProvider, $currentRolesProvider, null, null, $accountFeaturesPolicy);
    }

    protected function canRegister(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnProperty($subject, Action::Edit, $user);
    }

    protected function canView(mixed $subject, User $user): bool
    {
        return $this->canDoThatOnProperty($subject, Action::View, $user);
    }

    protected function canList(mixed $subject, User $user): bool
    {
        return $this->canView($subject, $user);
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
        return $this->canDoThatOnProperty($subject, Action::Export, $user);
    }

    public function supports(mixed $subject): bool
    {
        return $subject instanceof TripCalculationLink;
    }

    public function canViewForProperty(Property $property, User $user): bool
    {
        if (!$this->propertyAuthorizer->canDoThat($property, Action::View, $user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->currentAccountHasFeature($user, AccountFeature::PropertyTripCalculators);
    }

    public function canRegisterForProperty(Property $property, User $user): bool
    {
        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->propertyAuthorizer->canDoThat($property, Action::Edit, $user)
            && $this->currentAccountHasFeature($user, AccountFeature::PropertyTripCalculators);
    }

    private function canDoThatOnProperty(TripCalculationLink $tripCalculationLink, Action $action, User $user): bool
    {
        if (!$tripCalculationLink->isForProperty()) {
            return $this->canManageAccountLink($tripCalculationLink, $user);
        }

        $propertyId = $tripCalculationLink->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $property) {
            return false;
        }

        if (!$this->propertyAuthorizer->canDoThat($property, $action, $user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->currentAccountHasFeature($user, AccountFeature::PropertyTripCalculators);
    }

    private function canManageAccountLink(TripCalculationLink $tripCalculationLink, User $user): bool
    {
        $currentAccountId = $this->currentAccountId($user);
        $accountId = $tripCalculationLink->accountId();

        if ($this->userHasRole($user, Role::Owner)
        && null !== $currentAccountId
        && null !== $accountId
        && $currentAccountId->equals($accountId)
        && $this->currentAccountHasFeature($user, AccountFeature::AccountTripCalculator)) {
            return true;
        }

        return $this->userHasRole($user, Role::Admin);
    }

    private function isAdministrator(User $user): bool
    {
        return $this->userHasRole($user, Role::Admin) || $this->userHasRole($user, Role::SuperAdmin);
    }
}
