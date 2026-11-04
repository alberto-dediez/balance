<?php

declare(strict_types=1);

namespace App\Property\Application\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentFilter;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Domain\Model\Property\PropertyScope;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;

final class DistributedPropertiesPolicy
{
    public function __construct(
        private readonly DistributorAssignmentRepository $distributorAssignmentRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly AccountFeaturesPolicy $accountFeaturesPolicy,
    ) {}

    public function distributes(AccountId $distributorAccountId, Property $property): bool
    {
        $propertyAccountId = $property->accountId();

        if (!$propertyAccountId instanceof AccountId || !$this->distributorPolicy->isActiveDistributor($distributorAccountId)) {
            return false;
        }

        if (!$this->accountFeaturesPolicy->isEnabled(AccountFeature::PropertyTripCalculators, $propertyAccountId)) {
            return false;
        }

        return $this->distributorAssignmentRepository->findForAccount($distributorAccountId, $propertyAccountId) instanceof DistributorAssignment
            || $this->distributorAssignmentRepository->findForProperty($distributorAccountId, $property->id()) instanceof DistributorAssignment;
    }

    public function scopeFor(AccountId $distributorAccountId, ?AccountFeature $requiredFeature = null): PropertyScope
    {
        if (!$this->distributorPolicy->isActiveDistributor($distributorAccountId)) {
            return new PropertyScope();
        }

        $accountIds = [];
        $propertyIds = [];

        $assignments = $this->distributorAssignmentRepository->findByFilter(
            new DistributorAssignmentFilter(distributorAccountId: $distributorAccountId, limit: 0),
        );

        foreach ($assignments as $assignment) {
            $accountId = $assignment->accountId();

            if ($accountId instanceof AccountId && $this->accountHasFeature($accountId, $requiredFeature)) {
                $accountIds[] = $accountId;
            }

            $propertyId = $assignment->propertyId();

            if ($propertyId instanceof PropertyId && $this->propertyHasFeature($propertyId, $requiredFeature)) {
                $propertyIds[] = $propertyId;
            }
        }

        return (new PropertyScope())->merge(new PropertyScope($accountIds, $propertyIds));
    }

    private function accountHasFeature(AccountId $accountId, ?AccountFeature $requiredFeature): bool
    {
        return !$requiredFeature instanceof AccountFeature || $this->accountFeaturesPolicy->isEnabled($requiredFeature, $accountId);
    }

    private function propertyHasFeature(PropertyId $propertyId, ?AccountFeature $requiredFeature): bool
    {
        if (!$requiredFeature instanceof AccountFeature) {
            return true;
        }

        $property = $this->propertyRepository->find($propertyId);

        if (!$property instanceof Property || $property->isRemoved()) {
            return false;
        }

        $accountId = $property->accountId();

        return $accountId instanceof AccountId && $this->accountFeaturesPolicy->isEnabled($requiredFeature, $accountId);
    }
}
