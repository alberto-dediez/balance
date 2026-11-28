<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contest;

use App\CarbonFootprint\Application\Trip\TripCalculationContributionOrigin;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest\ContestPublicHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution\PropertyTripContributionTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/result/{tripCalculationId}/contest/thanks',
    name: 'public_carbon_footprint_property_trip_contest_thanks',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/result/{tripCalculationId}/contest/thanks',
    name: 'public_carbon_footprint_trip_contest_thanks',
    methods: ['GET'],
)]
class ThanksController extends AbstractController
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
        ContestPublicHandler $contestHandler,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $tripCalculation = $this->resolveTripCalculation($tripCalculationId, $link, $tripCalculationRepository);
        $accountId = $this->accountIdOf($link);

        if (null === $tripCalculation || !$accountId instanceof AccountId) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $routes = $this->contributionRoutes($link->tripCalculationLink, $token, $tripCalculationId);
        $host = ContestPublicHandler::hostForTripCalculationLink($link->tripCalculationLink);
        $contest = $contestHandler->activeContest($host);
        $context = new ContributionContext(
            accountId: $accountId,
            co2eqKg: (float) $tripCalculation->emissions()->co2eq(),
            origin: TripCalculationContributionOrigin::for($link->tripCalculationLink),
            originId: $tripCalculationId,
            originName: $link->property?->name() ?? ($tripCalculation->destinationLabel() ?? $link->ownerName),
            token: $token,
            allowSharedCredit: true,
            propertyId: $link->property?->id(),
        );

        $entry = null === $contest ? null : $contestHandler->entryFor($contest, $context);

        if (null === $contest || null === $entry) {
            return $this->redirectToRoute($routes->result, $routes->resultRouteParams);
        }

        return $this->render('@carbon_footprint/public/contest/thanks.html.twig', [
            'token' => $token,
            'property' => $link->property,
            'ownerName' => $link->ownerName,
            'asksForDestination' => $link->asksForDestination,
            'routes' => $routes,
            'contest' => $contest,
            'contestEntry' => $entry,
        ]);
    }
}
