<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Application\Trip\TripCalculationContributionOrigin;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/carbon-footprint/property-trip/{token}/contribution/{contributionId}/cancel',
    name: 'public_carbon_footprint_property_trip_contribution_cancel',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/contribution/{contributionId}/cancel',
    name: 'public_carbon_footprint_trip_contribution_cancel',
    methods: ['GET'],
)]
class CancelController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $contributionId,
        GetTripCalculationLinkByTokenService $getByToken,
        ProjectContributionRepository $projectContributionRepository,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $projectContribution = $projectContributionRepository->find(new ProjectContributionId($contributionId));
        $accountId = $this->accountIdOf($link);
        $contributionAccountId = $projectContribution?->accountId();
        $projectId = $projectContribution?->projectId();
        $originId = $projectContribution?->originId();

        if (
            null === $projectContribution
            || null === $accountId
            || null === $contributionAccountId
            || !$accountId->equals($contributionAccountId)
            || null === $projectId
            || null === $originId
            || TripCalculationContributionOrigin::for($link->tripCalculationLink) !== $projectContribution->origin()
        ) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $this->addFlash('warning', new TranslatableMessage('Has cancelado el pago. Puedes volver a intentarlo cuando quieras.'));

        return $this->redirectToRoute(PublicTripRoutes::for($link->tripCalculationLink)->name('_contribution_checkout'), [
            'token' => $token,
            'tripCalculationId' => $originId,
            'projectId' => (string) $projectId,
        ]);
    }
}
