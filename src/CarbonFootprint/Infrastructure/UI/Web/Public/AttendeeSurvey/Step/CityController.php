<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\Step;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseRequest;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\AttendeeSurvey\AttendeeSurveyPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/attendee-survey/{token}/city', name: 'public_carbon_footprint_attendee_survey_city')]
class CityController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $attendeeSurveyTokenLimiter,
        private RateLimiterFactory $attendeeSurveyInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetAttendeeSurveyByTokenService $getByToken,
        RegisterAttendeeSurveyResponseService $registerResponse,
        Request $request,
    ): Response {
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

        /** @var array<string, mixed>|null $intercityData */
        $intercityData = $session->get($sessionKey);

        if (null === $intercityData) {
            return $this->redirectToRoute('public_carbon_footprint_attendee_survey_intercity', ['token' => $token]);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('attendee_survey_city_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $city = $request->request->all('city');

            $intercityDays = $intercityData['intercityDays'] ?? null;
            $intercityMobilityId = $intercityData['intercityMobilityId'] ?? null;
            $intercityVehicleOccupants = $intercityData['intercityVehicleOccupants'] ?? null;
            $intercityDistanceKm = $intercityData['intercityDistanceKm'] ?? null;
            $hotelNights = $intercityData['hotelNights'] ?? null;

            try {
                $registerResponse(new RegisterAttendeeSurveyResponseRequest(
                    token: $token,
                    cameFromAnotherCity: (bool) $intercityData['cameFromAnotherCity'],
                    intercityDays: null !== $intercityDays ? (int) $intercityDays : null,
                    intercityMobilityId: null !== $intercityMobilityId ? (string) $intercityMobilityId : null,
                    intercityVehicleOccupants: null !== $intercityVehicleOccupants ? (int) $intercityVehicleOccupants : null,
                    intercityDistanceKm: null !== $intercityDistanceKm ? (float) $intercityDistanceKm : null,
                    usedHotel: (bool) ($intercityData['usedHotel'] ?? false),
                    hotelNights: null !== $hotelNights ? (int) $hotelNights : null,
                    cityDays: !empty($city['days']) ? (int) $city['days'] : null,
                    cityMobilityId: !empty($city['mobilityId']) ? (string) $city['mobilityId'] : null,
                    cityVehicleOccupants: !empty($city['vehicleOccupants']) ? (int) $city['vehicleOccupants'] : null,
                    cityDistanceKm: !empty($city['distanceKm']) ? (float) str_replace(',', '.', (string) $city['distanceKm']) : null,
                ));
            } catch (AttendeeSurveyTokenInvalidException) {
                return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyInvalidTokenLimiter, $request);
            } catch (AttendeeSurveyClosedException) {
                return $this->attendeeSurveyClosedResponse();
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido guardar la respuesta: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('public_carbon_footprint_attendee_survey_city', ['token' => $token]);
            }

            $session->remove($sessionKey);
            $session->set('attendee_survey_' . $token . '_answered', true);

            return $this->redirectToRoute('public_carbon_footprint_attendee_survey_thanks', ['token' => $token]);
        }

        return $this->render('@carbon_footprint/public/attendee_survey/step/city.html.twig', [
            'token' => $token,
            'event' => $response->event,
            'mobilities' => $response->mobilities,
        ]);
    }
}
