<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contest;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Contest\ContestCountries;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest\ContestPublicHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution\PropertyTripContributionTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/contest/terms',
    name: 'public_carbon_footprint_property_trip_contest_terms',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/contest/terms',
    name: 'public_carbon_footprint_trip_contest_terms',
    methods: ['GET'],
)]
class TermsController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        GetTripCalculationLinkByTokenService $getByToken,
        ContestPublicHandler $contestHandler,
        ContestCountries $countries,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $contest = $contestHandler->activeContest(ContestPublicHandler::hostForTripCalculationLink($link->tripCalculationLink));

        if (null === $contest) {
            return $this->redirectToRoute(PublicTripRoutes::for($link->tripCalculationLink)->prefix, ['token' => $token]);
        }

        return $this->render('@carbon_footprint/public/contest/terms.html.twig', [
            'token' => $token,
            'property' => $link->property,
            'ownerName' => $link->ownerName,
            'asksForDestination' => $link->asksForDestination,
            'contest' => $contest,
            'countries' => $countries->countries(),
            'layout' => '@carbon_footprint/public/property_trip/layout.html.twig',
        ]);
    }
}
