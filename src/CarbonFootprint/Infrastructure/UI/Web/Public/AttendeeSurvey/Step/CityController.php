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
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Trip\LegInputsHttpMapper;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/attendee-survey/{token}/city', name: 'public_carbon_footprint_attendee_survey_city')]
class CityController extends AbstractController
{
    use AttendeeSurveyPublicControllerTrait;
    use BrandedPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $attendeeSurveyRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetAttendeeSurveyByTokenService $getByToken,
        RegisterAttendeeSurveyResponseService $registerResponse,
        Request $request,
    ): Response {
        if ($limited = $this->attendeeSurveyRateLimiter->check($request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetAttendeeSurveyByTokenRequest($token));
        } catch (AttendeeSurveyTokenInvalidException) {
            return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
        }

        $this->brandFor($response->attendeeSurvey->accountId());

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

            if (ConsentRequestReader::accepted($request)) {
                $city = $request->request->all('city');

                $hotelNights = $intercityData['hotelNights'] ?? null;
                $hotelPeople = $intercityData['hotelPeople'] ?? null;
                $intercityTrips = $intercityData['intercityTrips'] ?? null;

                /** @var array<mixed> $intercityLegs */
                $intercityLegs = is_array($intercityData['intercityLegs'] ?? null) ? $intercityData['intercityLegs'] : [];

                try {
                    $registered = $registerResponse(new RegisterAttendeeSurveyResponseRequest(
                        token: $token,
                        cameFromAnotherCity: (bool) $intercityData['cameFromAnotherCity'],
                        originPlaceReference: null !== ($intercityData['originPlaceReference'] ?? null) ? (string) $intercityData['originPlaceReference'] : null,
                        usedHotel: (bool) ($intercityData['usedHotel'] ?? false),
                        accommodationPlaceReference: null !== ($intercityData['accommodationPlaceReference'] ?? null) ? (string) $intercityData['accommodationPlaceReference'] : null,
                        hotelNights: null !== $hotelNights ? (int) $hotelNights : null,
                        hotelPeople: null !== $hotelPeople ? (int) $hotelPeople : null,
                        intercityRoundTrip: (bool) ($intercityData['intercityRoundTrip'] ?? false),
                        intercityTrips: null !== $intercityTrips ? (int) $intercityTrips : null,
                        intercityLegs: LegInputsHttpMapper::toLegInputs($intercityLegs),
                        movedWithinCity: 'yes' === ($city['moved'] ?? null),
                        cityOriginPlaceReference: !empty($city['originPlaceReference']) ? (string) $city['originPlaceReference'] : null,
                        cityRoundTrip: 'yes' === ($city['roundTrip'] ?? null),
                        cityTrips: !empty($city['trips']) ? (int) $city['trips'] : null,
                        cityLegs: LegInputsHttpMapper::toLegInputs(LegInputsHttpMapper::toSessionData($city)),
                        consentIp: ConsentRequestReader::ip($request),
                        consentUserAgent: ConsentRequestReader::userAgent($request),
                        consentAcceptLanguage: ConsentRequestReader::acceptLanguage($request),
                    ));
                } catch (AttendeeSurveyTokenInvalidException) {
                    return $this->attendeeSurveyInvalidResponse($this->attendeeSurveyRateLimiter, $request);
                } catch (AttendeeSurveyClosedException) {
                    return $this->attendeeSurveyClosedResponse();
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('danger', new TranslatableMessage('No se ha podido guardar la respuesta: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

                    return $this->redirectToRoute('public_carbon_footprint_attendee_survey_city', ['token' => $token]);
                }

                $session->remove($sessionKey);
                $session->set('attendee_survey_' . $token . '_answered', true);

                if ((float) $registered->emissions->co2eq() <= 0.0) {
                    return $this->redirectToRoute('public_carbon_footprint_attendee_survey_thanks', ['token' => $token]);
                }

                return $this->redirectToRoute('public_carbon_footprint_attendee_survey_result', [
                    'token' => $token,
                    'responseId' => (string) $registered->attendeeSurveyResponseId,
                ]);
            }

            $this->addFlash('danger', new TranslatableMessage('Debes aceptar el tratamiento de tus datos para continuar.'));
        }

        return $this->render('@carbon_footprint/public/attendee_survey/step/city.html.twig', [
            'token' => $token,
            'event' => $response->event,
            'mobilities' => $response->mobilities,
            'geoEnabled' => $response->geoEnabled,
            'maxLegs' => $response->maxLegs,
            'accommodationPlaceReference' => $intercityData['accommodationPlaceReference'] ?? null,
            'accommodationLabel' => $intercityData['accommodationLabel'] ?? null,
        ]);
    }
}
