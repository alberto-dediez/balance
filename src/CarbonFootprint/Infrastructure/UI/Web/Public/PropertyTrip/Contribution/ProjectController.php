<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Library\Domain\Model\Media\MediaRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/carbon-footprint/property-trip/{token}/result/{tripCalculationId}/compensate/{projectId}/details',
    name: 'public_carbon_footprint_property_trip_contribution_project',
    methods: ['GET'],
)]
#[Route(
    '/carbon-footprint/trip/{token}/result/{tripCalculationId}/compensate/{projectId}/details',
    name: 'public_carbon_footprint_trip_contribution_project',
    methods: ['GET'],
)]
class ProjectController extends AbstractController
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
        ListContributionOptionsService $listContributionOptions,
        MediaRepository $mediaRepository,
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

        $options = $listContributionOptions(new ListContributionOptionsRequest(
            accountId: $accountId,
            co2eqKg: (float) $tripCalculation->emissions()->co2eq(),
        ));

        $option = $options->find($projectId);

        if (null === $option) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $gallery = $this->publicGallery($option->project->gallery(), $mediaRepository);

        return $this->render('@carbon_footprint/project_contribution/project.html.twig', [
            'token' => $token,
            'property' => $link->property,
            'ownerName' => $link->ownerName,
            'routes' => $this->contributionRoutes($link->tripCalculationLink, $token, $tripCalculationId),
            'project' => $option->project,
            'option' => $option,
            'gallery' => $gallery,
        ]);
    }
}
