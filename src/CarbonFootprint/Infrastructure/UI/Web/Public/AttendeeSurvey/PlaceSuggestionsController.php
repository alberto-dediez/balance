<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
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

#[Route('/carbon-footprint/attendee-survey/{token}/places', name: 'public_carbon_footprint_attendee_survey_places', methods: ['GET'])]
class PlaceSuggestionsController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        #[MapQueryParameter]
        string $query,
        GetAttendeeSurveyByTokenService $getByToken,
        SuggestPlacesService $suggestPlaces,
        Request $request,
        #[MapQueryParameter]
        ?string $kind = null,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        if (!$this->attendeeSurveyRateLimiter->checkPlaces($request)) {
            return new JsonResponse([], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $survey = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            $this->attendeeSurveyRateLimiter->penalizeInvalidToken($request);

            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        if ($survey->attendeeSurvey->status()->isClosed()) {
            return new JsonResponse([], Response::HTTP_CONFLICT);
        }

        $response = $suggestPlaces(new SuggestPlacesRequest(
            query: $query,
            kind: PlaceKind::tryFrom((string) $kind) ?? PlaceKind::Any,
        ));

        return new JsonResponse(PlaceSuggestionsPresenter::toChoices($response->suggestions));
    }
}
