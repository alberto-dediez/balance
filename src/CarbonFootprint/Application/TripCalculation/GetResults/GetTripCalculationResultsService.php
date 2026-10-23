<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

final class GetTripCalculationResultsService
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyAuthorizer $propertyAuthorizer,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationResultsBuilder $tripCalculationResultsBuilder,
        private PropertyLocator $propertyLocator,
    ) {}

    public function __invoke(GetTripCalculationResultsRequest $request, User $user): GetTripCalculationResultsResponse
    {
        $property = $this->propertyRepository->find($request->propertyId);

        if (null === $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $this->propertyAuthorizer->denyUnlessCanDoThat($property, Action::View, $user);

        $tripCalculationLink = $this->tripCalculationLinkRepository->findByPropertyId($request->propertyId);
        $destination = $this->propertyLocator->locate($property);

        if (null === $tripCalculationLink) {
            return $this->tripCalculationResultsBuilder->empty($property, $property->name(), $destination);
        }

        return $this->tripCalculationResultsBuilder->build(
            $tripCalculationLink,
            $property,
            $property->name(),
            $destination,
            $request->from,
            $request->to,
        );
    }
}
