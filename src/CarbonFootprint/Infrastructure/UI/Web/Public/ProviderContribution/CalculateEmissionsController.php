<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\CalculateEmissions\CalculateProviderContributionActivityEmissionsRequest;
use App\CarbonFootprint\Application\ProviderContribution\CalculateEmissions\CalculateProviderContributionActivityEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/contribution/{token}/emissions/calculate', name: 'public_carbon_footprint_contribution_emissions_calculate', methods: ['POST'])]
class CalculateEmissionsController extends AbstractController
{
    use ProviderContributionPublicControllerTrait;

    public function __construct(private RateLimiterFactory $providerContributionTokenLimiter) {}

    public function __invoke(
        string $token,
        CalculateProviderContributionActivityEmissionsService $calculateActivityEmissions,
        Request $request,
    ): JsonResponse|Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $request)) {
            return $limited;
        }

        $payload = $request->getPayload();

        $activityType = ActivityType::tryFrom($payload->getString('type'));
        $condition = ItemCondition::tryFrom($payload->getString('condition'));
        $unit = Unit::tryFrom($payload->getString('unit'));
        $itemId = $payload->getString('itemId');
        $secondaryItemId = $payload->getString('itemId2');

        $hasRequiredItem = '' !== $itemId;

        if (null === $activityType || !$hasRequiredItem || null === $unit) {
            return $this->json(['error' => 'Invalid parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $quantity = new Quantity((float) str_replace(',', '.', $payload->getString('quantity')), $unit);

            $response = $calculateActivityEmissions(new CalculateProviderContributionActivityEmissionsRequest(
                token: $token,
                activityType: $activityType,
                itemId: $itemId,
                condition: $condition,
                quantity: $quantity,
                secondaryItemId: '' !== $secondaryItemId ? $secondaryItemId : null,
            ));
        } catch (ProviderContributionTokenInvalidException|ProviderContributionIgnoredException) {
            return $this->json(['error' => 'Invalid token'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'emissions' => [
                'co2eq' => $response->emissions->co2eq(),
                'co2' => $response->emissions->co2(),
                'ch4' => $response->emissions->ch4(),
                'n2o' => $response->emissions->n2o(),
            ],
            'emissionFactor' => [
                'co2eq' => $response->emissionFactor->co2eq(),
                'unit' => $response->emissionFactor->unit()->value,
                'year' => $response->emissionFactor->year(),
            ],
        ]);
    }
}
