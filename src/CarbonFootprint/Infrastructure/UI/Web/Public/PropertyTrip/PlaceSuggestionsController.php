<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Shared\Application\Place\Suggest\SuggestPlacesRequest;
use App\Shared\Application\Place\Suggest\SuggestPlacesService;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Infrastructure\UI\Web\Place\PlaceSuggestionsPresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}/places', name: 'public_carbon_footprint_property_trip_places', methods: ['GET'])]
class PlaceSuggestionsController extends AbstractController
{
    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        #[MapQueryParameter]
        string $query,
        GetTripCalculationLinkByTokenService $getByToken,
        SuggestPlacesService $suggestPlaces,
        Request $request,
        #[MapQueryParameter]
        ?string $kind = null,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        if (!$this->propertyTripRateLimiter->checkPlaces($request)) {
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            $this->propertyTripRateLimiter->penalizeInvalidToken($request);

            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $response = $suggestPlaces(new SuggestPlacesRequest(
            query: $query,
            kind: PlaceKind::tryFrom((string) $kind) ?? PlaceKind::Any,
        ));

        return new JsonResponse(PlaceSuggestionsPresenter::toChoices($response->suggestions));
    }
}
