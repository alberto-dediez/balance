<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsRequest;
use App\Balance\Application\ProjectContribution\Options\ListContributionOptionsService;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\UI\Web\Branding\BrandedPublicControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}/result/{tripCalculationId}', name: 'public_carbon_footprint_property_trip_result', methods: ['GET'])]
class ResultController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use BrandedPublicControllerTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $tripCalculationId,
        GetTripCalculationLinkByTokenService $getByToken,
        TripCalculationRepository $tripCalculationRepository,
        ListContributionOptionsService $listContributionOptions,
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

        $tripCalculation = $tripCalculationRepository->find(new TripCalculationId($tripCalculationId));
        $linkId = $tripCalculation?->tripCalculationLinkId();

        if (!$linkId instanceof TripCalculationLinkId || !$linkId->equals($response->tripCalculationLink->id())) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $mobilityNames = [];

        foreach ($response->mobilities as $mobility) {
            $mobilityNames[(string) $mobility->id()] = $mobility->name();
        }

        $accountId = $response->tripCalculationLink->accountId() ?? $response->property->accountId();
        $hasContributionOptions = false;

        if ($accountId instanceof AccountId) {
            $options = $listContributionOptions(new ListContributionOptionsRequest(
                accountId: $accountId,
                co2eqKg: (float) $tripCalculation->emissions()->co2eq(),
            ));

            $hasContributionOptions = !$options->isEmpty();
        }

        return $this->render('@carbon_footprint/public/property_trip/result.html.twig', [
            'token' => $token,
            'property' => $response->property,
            'tripCalculation' => $tripCalculation,
            'mobilityNames' => $mobilityNames,
            'hasContributionOptions' => $hasContributionOptions,
            'scope' => $response->tripCalculationLink->scope(),
        ]);
    }
}
