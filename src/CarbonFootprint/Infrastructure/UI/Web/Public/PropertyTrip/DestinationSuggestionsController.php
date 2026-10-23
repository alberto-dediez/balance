<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations\SuggestTripDestinationsRequest;
use App\CarbonFootprint\Application\TripCalculationLink\SuggestDestinations\SuggestTripDestinationsService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Shared\Infrastructure\UI\Web\Place\PlaceSuggestionsPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}/destinations', name: 'public_carbon_footprint_property_trip_destinations', methods: ['GET'])]
#[Route('/carbon-footprint/trip/{token}/destinations', name: 'public_carbon_footprint_trip_destinations', methods: ['GET'])]
class DestinationSuggestionsController extends AbstractController
{
    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        #[MapQueryParameter]
        string $query,
        SuggestTripDestinationsService $suggestTripDestinations,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        if (!$this->propertyTripRateLimiter->checkPlaces($request)) {
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $response = $suggestTripDestinations(new SuggestTripDestinationsRequest($token, $query));
        } catch (TripCalculationLinkTokenInvalidException) {
            $this->propertyTripRateLimiter->penalizeInvalidToken($request);

            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(PlaceSuggestionsPresenter::toChoices($response->suggestions));
    }
}
