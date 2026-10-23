<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations;

use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\Trip\TripDestinationResolver;
use App\CarbonFootprint\Application\Trip\TripDestinationSearchArea;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Geo\PlaceFinder;
use App\Shared\Domain\Model\Geo\PlaceSuggestion;
use App\Shared\Domain\Model\Locale\LocaleGetter;

final class SuggestTripDestinationsService
{
    private const int MINIMUM_QUERY_LENGTH = 3;

    private const int MAX_PROPERTY_SUGGESTIONS = 10;

    public function __construct(
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly PlaceFinder $placeFinder,
        private readonly TripDestinationCandidates $tripDestinationCandidates,
        private readonly TripDestinationSearchArea $tripDestinationSearchArea,
    ) {}

    public function __invoke(SuggestTripDestinationsRequest $request): SuggestTripDestinationsResponse
    {
        $tripCalculationLink = $this->tripCalculationLinkRepository->findByToken($request->token);

        if (null === $tripCalculationLink || $tripCalculationLink->isForProperty()) {
            throw new TripCalculationLinkTokenInvalidException();
        }

        $query = trim($request->query);

        if (mb_strlen($query) < self::MINIMUM_QUERY_LENGTH) {
            return new SuggestTripDestinationsResponse([]);
        }

        if ($tripCalculationLink->destinationMode()->usesProperties()) {
            return new SuggestTripDestinationsResponse($this->propertySuggestions($tripCalculationLink, $query));
        }

        return new SuggestTripDestinationsResponse($this->placeFinder->suggest(
            $query,
            $tripCalculationLink->destinationPlaceKind()->toPlaceKind(),
            LocaleGetter::locale(),
            $this->tripDestinationSearchArea->forLink($tripCalculationLink),
        ));
    }

    /** @return list<PlaceSuggestion> */
    private function propertySuggestions(TripCalculationLink $tripCalculationLink, string $query): array
    {
        $properties = array_filter(
            $this->tripDestinationCandidates->allowedFor($tripCalculationLink),
            static fn(Property $property): bool => false !== mb_stripos($property->name(), $query),
        );

        return array_map(
            static fn(Property $property): PlaceSuggestion => new PlaceSuggestion(
                reference: TripDestinationResolver::PROPERTY_REFERENCE_PREFIX . $property->id(),
                label: $property->name(),
            ),
            array_slice(array_values($properties), 0, self::MAX_PROPERTY_SUGGESTIONS),
        );
    }
}
