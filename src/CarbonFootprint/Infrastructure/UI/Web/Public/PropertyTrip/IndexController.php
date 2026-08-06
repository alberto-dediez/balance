<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}', name: 'public_carbon_footprint_property_trip', methods: ['GET'])]
class IndexController extends AbstractController
{
    use PropertyTripPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(string $token, GetTripCalculationLinkByTokenService $getByToken, Request $request): Response
    {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        try {
            $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        return $this->redirectToRoute('public_carbon_footprint_property_trip_trip', ['token' => $token]);
    }
}
