<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Contribution;

use App\Balance\Domain\Model\ProjectContribution\DistributedContributionsResolver;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionScope;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;

final readonly class PropertyTripDistributedContributionsResolver implements DistributedContributionsResolver
{
    public function __construct(
        private DistributedPropertiesPolicy $distributedPropertiesPolicy,
        private PropertyRepository $propertyRepository,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationRepository $tripCalculationRepository,
    ) {}

    public function scopeFor(AccountId $distributorAccountId): ProjectContributionScope
    {
        $propertyScope = $this->distributedPropertiesPolicy->scopeFor($distributorAccountId, AccountFeature::PropertyTripCalculators);

        $scope = new ProjectContributionScope(propertyAccountIds: $propertyScope->accountIds);

        foreach ($propertyScope->propertyIds as $propertyId) {
            $scope = $scope->merge($this->scopeOfProperty($propertyId));
        }

        return $scope;
    }

    public function propertyScopeFor(AccountId $viewerAccountId, PropertyId $propertyId, bool $includeDistributed): ProjectContributionScope
    {
        $property = $this->propertyRepository->find($propertyId);

        if (!$property instanceof Property || $property->isRemoved()) {
            return new ProjectContributionScope();
        }

        $propertyAccountId = $property->accountId();
        $ownsProperty = $propertyAccountId instanceof AccountId && $propertyAccountId->equals($viewerAccountId);

        if (!$ownsProperty && !($includeDistributed && $this->distributedPropertiesPolicy->distributes($viewerAccountId, $property))) {
            return new ProjectContributionScope();
        }

        return $this->scopeOfProperty($propertyId);
    }

    public function distributes(AccountId $distributorAccountId, ProjectContribution $projectContribution): bool
    {
        $property = $this->propertyOf($projectContribution);

        if (!$property instanceof Property) {
            return false;
        }

        $propertyAccountId = $property->accountId();
        $contributionAccountId = $projectContribution->accountId();

        return $propertyAccountId instanceof AccountId
            && $contributionAccountId instanceof AccountId
            && $propertyAccountId->equals($contributionAccountId)
            && $this->distributedPropertiesPolicy->distributes($distributorAccountId, $property);
    }

    private function scopeOfProperty(PropertyId $propertyId): ProjectContributionScope
    {
        $link = $this->tripCalculationLinkRepository->findByPropertyId($propertyId);

        $tripCalculationIds = $link instanceof TripCalculationLink
            ? array_map(
                static fn(TripCalculation $tripCalculation): string => (string) $tripCalculation->id(),
                $this->tripCalculationRepository->findByLinkId($link->id()),
            )
            : [];

        return new ProjectContributionScope(
            propertyIds: [(string) $propertyId],
            propertyTripCalculationIds: $tripCalculationIds,
        );
    }

    private function propertyOf(ProjectContribution $projectContribution): ?Property
    {
        $originId = $projectContribution->originId();

        if (null === $originId) {
            return null;
        }

        $propertyId = match ($projectContribution->origin()) {
            ProjectContributionOrigin::Property => new PropertyId($originId),
            ProjectContributionOrigin::PropertyTripCalculation => $this->propertyIdOfTripCalculation($originId),
            default => null,
        };

        if (!$propertyId instanceof PropertyId) {
            return null;
        }

        $property = $this->propertyRepository->find($propertyId);

        return $property instanceof Property && !$property->isRemoved() ? $property : null;
    }

    private function propertyIdOfTripCalculation(string $tripCalculationId): ?PropertyId
    {
        $tripCalculation = $this->tripCalculationRepository->find(new TripCalculationId($tripCalculationId));
        $linkId = $tripCalculation?->tripCalculationLinkId();

        if (!$linkId instanceof TripCalculationLinkId) {
            return null;
        }

        return $this->tripCalculationLinkRepository->find($linkId)?->propertyId();
    }
}
