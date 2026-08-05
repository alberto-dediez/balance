<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Step;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\LegInputsHttpMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/attendee-survey/{token}/intercity', name: 'public_carbon_footprint_attendee_survey_intercity')]
class IntercityController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $attendeeSurveyTokenLimiter,
        private RateLimiterFactory $attendeeSurveyInvalidTokenLimiter,
    ) {}

    public function __invoke(string $token, GetAttendeeSurveyByTokenService $getByToken, Request $request): Response
    {
        if ($limited = $this->checkAttendeeSurveyRateLimit($this->attendeeSurveyTokenLimiter, $this->attendeeSurveyInvalidTokenLimiter, $request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyInvalidTokenLimiter, $request);
        }

        if ($response->attendeeSurvey->status()->isClosed()) {
            return $this->attendeeSurveyClosedResponse();
        }

        $session = $request->getSession();
        $sessionKey = 'attendee_survey_' . $token;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('attendee_survey_intercity_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $intercity = $request->request->all('intercity');

            $cameFromAnotherCity = 'yes' === ($intercity['cameFromAnotherCity'] ?? null);
            $usedHotel = 'yes' === ($intercity['usedHotel'] ?? null);

            $session->set($sessionKey, [
                'cameFromAnotherCity' => $cameFromAnotherCity,
                'originPlaceReference' => !empty($intercity['originPlaceReference']) ? (string) $intercity['originPlaceReference'] : null,
                'usedHotel' => $usedHotel,
                'accommodationPlaceReference' => !empty($intercity['accommodationPlaceReference']) ? (string) $intercity['accommodationPlaceReference'] : null,
                'accommodationLabel' => !empty($intercity['accommodationLabel']) ? (string) $intercity['accommodationLabel'] : null,
                'hotelNights' => !empty($intercity['hotelNights']) ? (int) $intercity['hotelNights'] : null,
                'intercityRoundTrip' => 'yes' === ($intercity['roundTrip'] ?? null),
                'intercityTrips' => !empty($intercity['trips']) ? (int) $intercity['trips'] : null,
                'intercityLegs' => LegInputsHttpMapper::toSessionData($intercity),
            ]);

            return $this->redirectToRoute('public_carbon_footprint_attendee_survey_city', ['token' => $token]);
        }

        /** @var array<string, mixed> $stored */
        $stored = $session->get($sessionKey, []);

        return $this->render('@carbon_footprint/public/attendee_survey/step/intercity.html.twig', [
            'token' => $token,
            'event' => $response->event,
            'mobilities' => $response->mobilities,
            'stored' => $stored,
            'geoEnabled' => $response->geoEnabled,
            'maxLegs' => $response->maxLegs,
        ]);
    }
}
