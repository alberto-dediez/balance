<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/result/{tripCalculationId}/contact/thanks',
    name: 'public_carbon_footprint_property_trip_contribution_contact_thanks',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/result/{tripCalculationId}/contact/thanks',
    name: 'public_carbon_footprint_trip_contribution_contact_thanks',
    methods: ['GET'],
)]
class ContactThanksController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $tripCalculationId,
        GetTripCalculationLinkByTokenService $getByToken,
        TripCalculationRepository $tripCalculationRepository,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link || null === $this->resolveTripCalculation($tripCalculationId, $link, $tripCalculationRepository)) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        return $this->render('@carbon_footprint/project_contribution/contact_thanks.html.twig', [
            'token' => $token,
            'property' => $link->property,
            'ownerName' => $link->ownerName,
            'asksForDestination' => $link->asksForDestination,
            'routes' => $this->contributionRoutes($link->tripCalculationLink, $token, $tripCalculationId),
        ]);
    }
}
