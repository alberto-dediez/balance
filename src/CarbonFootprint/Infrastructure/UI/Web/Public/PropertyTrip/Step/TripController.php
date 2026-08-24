<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Step;

use App\CarbonFootprint\Application\TripCalculation\Register\RegisterTripCalculationRequest;
use App\CarbonFootprint\Application\TripCalculation\Register\RegisterTripCalculationService;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Trip\LegInputsHttpMapper;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/carbon-footprint/property-trip/{token}/trip', name: 'public_carbon_footprint_property_trip_trip')]
class TripController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use BrandedPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
        RegisterTripCalculationService $registerTripCalculation,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        try {
            $response = $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $this->brandFor($response->tripCalculationLink->accountId());

        $scope = $response->tripCalculationLink->scope();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('property_trip_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            if (ConsentRequestReader::accepted($request)) {
                $trip = $request->request->all('trip');

                $cameFromAnotherCity = $scope->includesTransport()
                    && (!$scope->asksForBoth() || 'yes' === ($trip['cameFromAnotherCity'] ?? null));
                $overnight = $scope->includesOvernightStay()
                    && (!$scope->asksForBoth() || 'yes' === ($trip['overnight'] ?? null));

                try {
                    $registered = $registerTripCalculation(new RegisterTripCalculationRequest(
                        token: $token,
                        originPlaceReference: $cameFromAnotherCity && !empty($trip['originPlaceReference']) ? (string) $trip['originPlaceReference'] : null,
                        roundTrip: $cameFromAnotherCity && 'yes' === ($trip['roundTrip'] ?? null),
                        trips: $cameFromAnotherCity && !empty($trip['trips']) ? (int) $trip['trips'] : 1,
                        nights: $overnight && !empty($trip['nights']) ? (int) $trip['nights'] : 0,
                        legs: $cameFromAnotherCity ? LegInputsHttpMapper::toLegInputs(LegInputsHttpMapper::toSessionData($trip)) : [],
                        consentIp: ConsentRequestReader::ip($request),
                        consentUserAgent: ConsentRequestReader::userAgent($request),
                        consentAcceptLanguage: ConsentRequestReader::acceptLanguage($request),
                    ));
                } catch (TripCalculationLinkTokenInvalidException) {
                    return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('danger', new TranslatableMessage('No se ha podido calcular tu huella: {error}', ['{error}' => $e->getMessage()]));

                    return $this->redirectToRoute('public_carbon_footprint_property_trip_trip', ['token' => $token]);
                }

                return $this->redirectToRoute('public_carbon_footprint_property_trip_result', [
                    'token' => $token,
                    'tripCalculationId' => (string) $registered->tripCalculationId,
                ]);
            }

            $this->addFlash('danger', new TranslatableMessage('Debes aceptar el tratamiento de tus datos para continuar.'));
        }

        return $this->render('@carbon_footprint/public/property_trip/step/trip.html.twig', [
            'token' => $token,
            'property' => $response->property,
            'mobilities' => $response->mobilities,
            'geoEnabled' => $response->geoEnabled,
            'maxLegs' => $response->maxLegs,
            'scope' => $scope,
        ]);
    }
}
