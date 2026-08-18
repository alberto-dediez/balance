<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCheckoutHandler;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/result/{tripCalculationId}/compensate/{projectId}',
    name: 'public_carbon_footprint_property_trip_contribution_checkout',
    methods: ['GET', 'POST'],
)]
class CheckoutController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $tripCalculationId,
        string $projectId,
        GetTripCalculationLinkByTokenService $getByToken,
        TripCalculationRepository $tripCalculationRepository,
        ContributionCheckoutHandler $checkoutHandler,
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

        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('property_trip_' . $token, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF no válido');
        }

        $result = $checkoutHandler->handle(
            new ContributionContext(
                accountId: $accountId,
                co2eqKg: (float) $tripCalculation->emissions()->co2eq(),
                origin: ProjectContributionOrigin::PropertyTripCalculation,
                originId: $tripCalculationId,
                originName: $link->property->name(),
                token: $token,
            ),
            $this->contributionRoutes($token, $tripCalculationId),
            $projectId,
            $request,
        );

        if ($result->invalid) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        if (null !== $result->redirectUrl) {
            return $this->redirect($result->redirectUrl);
        }

        $parameters = $result->parameters + ['property' => $link->property];

        if ($request->isXmlHttpRequest()) {
            return $this->render('@carbon_footprint/project_contribution/_summary.html.twig', $parameters);
        }

        return $this->render('@carbon_footprint/project_contribution/checkout.html.twig', $parameters);
    }
}
