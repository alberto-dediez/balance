<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip;

use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenRequest;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkTokenInvalidException;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/property-trip/{token}/result/{tripCalculationId}', name: 'public_carbon_footprint_property_trip_result', methods: ['GET'])]
class ResultController extends AbstractController
{
    use PropertyTripPublicControllerTrait;

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

        try {
            $response = $getByToken(new GetTripCalculationLinkByTokenRequest($token));
        } catch (TripCalculationLinkTokenInvalidException) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $tripCalculation = $tripCalculationRepository->find(new TripCalculationId($tripCalculationId));
        $linkId = $tripCalculation?->tripCalculationLinkId();

        if (!$linkId instanceof TripCalculationLinkId || !$linkId->equals($response->tripCalculationLink->id())) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $mobilityNames = [];

        foreach ($response->mobilities as $mobility) {
            $mobilityNames[(string) $mobility->id()] = $mobility->name();
        }

        return $this->render('@carbon_footprint/public/property_trip/result.html.twig', [
            'token' => $token,
            'property' => $response->property,
            'tripCalculation' => $tripCalculation,
            'mobilityNames' => $mobilityNames,
        ]);
    }
}
