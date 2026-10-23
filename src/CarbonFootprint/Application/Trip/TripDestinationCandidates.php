<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Account\ConsultantSupervisionPolicy;
use App\Security\Domain\Model\Account\AccountId;

final class TripDestinationCandidates
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly ConsultantSupervisionPolicy $consultantSupervisionPolicy,
        private readonly PropertyLocator $propertyLocator,
    ) {}

    /** @return list<Property> */
    public function candidatesFor(AccountId $accountId): array
    {
        $accountIds = [$accountId, ...$this->consultantSupervisionPolicy->supervisedAccountIds($accountId)];

        $properties = array_values(array_filter(
            $this->propertyRepository->findByFilter(new PropertyFilter(accountIds: $accountIds, limit: 0)),
            fn(Property $property): bool => null !== $this->propertyLocator->locate($property),
        ));

        usort($properties, static fn(Property $a, Property $b): int => strcasecmp($a->name(), $b->name()));

        return $properties;
    }

    /** @return list<Property> */
    public function allowedFor(TripCalculationLink $tripCalculationLink): array
    {
        $accountId = $tripCalculationLink->accountId();

        if (null === $accountId || !$tripCalculationLink->destinationMode()->usesProperties()) {
            return [];
        }

        $candidates = $this->candidatesFor($accountId);

        if (TripDestinationMode::AllProperties === $tripCalculationLink->destinationMode()) {
            return $candidates;
        }

        $selected = array_map(
            static fn(PropertyId $propertyId): string => (string) $propertyId,
            $tripCalculationLink->destinationPropertyIds(),
        );

        return array_values(array_filter(
            $candidates,
            static fn(Property $property): bool => in_array((string) $property->id(), $selected, true),
        ));
    }
}
