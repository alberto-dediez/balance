<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Application\PublicLanding\GetActive\GetActivePublicLandingService;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicLanding\PublicLandingRenderTrait;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}', name: 'public_carbon_footprint_property_trip', methods: ['GET'])]
#[Route('/carbon-footprint/trip/{token}', name: 'public_carbon_footprint_trip', methods: ['GET'])]
class IndexController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use BrandedPublicControllerTrait;
    use PublicLandingRenderTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
        GetActivePublicLandingService $getActivePublicLanding,
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

        $routes = PublicTripRoutes::for($response->tripCalculationLink);

        $landing = $this->renderPublicLanding(
            getActivePublicLanding: $getActivePublicLanding,
            ownerType: $response->asksForDestination ? PublicLandingOwnerType::TripCalculationLink : PublicLandingOwnerType::Property,
            ownerId: $response->asksForDestination ? (string) $response->tripCalculationLink->id() : (string) $response->property?->id(),
            ownerName: $response->ownerName,
            ctaRoute: $routes->name('_trip'),
            ctaRouteParams: ['token' => $token],
        );

        return $landing ?? $this->redirectToRoute($routes->name('_trip'), ['token' => $token]);
    }
}
