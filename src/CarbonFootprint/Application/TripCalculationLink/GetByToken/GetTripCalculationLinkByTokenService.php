<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\GetByToken;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Shared\Domain\Model\Geo\PlaceFinder;

final class GetTripCalculationLinkByTokenService
{
    public function __construct(
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private PropertyRepository $propertyRepository,
        private MobilityRepository $mobilityRepository,
        private PropertyLocator $propertyLocator,
        private PlaceFinder $placeFinder,
        private int $maxLegs = 16,
    ) {}

    public function __invoke(GetTripCalculationLinkByTokenRequest $request): GetTripCalculationLinkByTokenResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->findByToken($request->token);

        if (null === $tripCalculationLink) {
            throw new TripCalculationLinkTokenInvalidException();
        }

        $propertyId = $tripCalculationLink->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        if (null === $property) {
            throw new TripCalculationLinkTokenInvalidException();
        }

        $mobilities = $this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0));

        return new GetTripCalculationLinkByTokenResponse(
            tripCalculationLink: $tripCalculationLink,
            property: $property,
            mobilities: $mobilities,
            geoEnabled: $this->placeFinder->isOperative() && null !== $this->propertyLocator->locate($property),
            maxLegs: $this->maxLegs,
        );
    }
}
