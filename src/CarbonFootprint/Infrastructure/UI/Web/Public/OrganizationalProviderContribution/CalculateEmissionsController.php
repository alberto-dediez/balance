<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions\CalculateOrganizationalProviderContributionActivityEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions\CalculateOrganizationalProviderContributionActivityEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateNotFoundException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/carbon-footprint/provider-contribution/{token}/emissions/calculate', name: 'public_organizational_provider_contribution_emissions_calculate', methods: ['POST'])]
class CalculateEmissionsController extends AbstractController
{
    use OrganizationalProviderContributionPublicControllerTrait;

    public function __construct(
        private RateLimiterFactory $providerContributionTokenLimiter,
        private RateLimiterFactory $providerContributionInvalidTokenLimiter,
    ) {}

    public function __invoke(
        string $token,
        CalculateOrganizationalProviderContributionActivityEmissionsService $calculateActivityEmissions,
        Request $request,
    ): JsonResponse|Response {
        if ($limited = $this->checkProviderContributionRateLimit($this->providerContributionTokenLimiter, $this->providerContributionInvalidTokenLimiter, $request)) {
            return $limited;
        }

        $payload = $request->getPayload();

        $activityType = ActivityType::tryFrom($payload->getString('type'));
        $condition = ItemCondition::tryFrom($payload->getString('condition'));
        $unit = Unit::tryFrom($payload->getString('unit'));
        $itemId = $payload->getString('itemId');
        $secondaryItemId = $payload->getString('itemId2');

        if (null === $activityType || '' === $itemId || null === $unit) {
            return $this->json(['error' => 'Invalid parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $response = $calculateActivityEmissions(new CalculateOrganizationalProviderContributionActivityEmissionsRequest(
                token: $token,
                activityType: $activityType,
                itemId: $itemId,
                condition: $condition,
                quantity: new Quantity((float) str_replace(',', '.', $payload->getString('quantity')), $unit),
                secondaryItemId: '' !== $secondaryItemId ? $secondaryItemId : null,
            ));
        } catch (OrganizationalProviderContributionTokenInvalidException|OrganizationalProviderContributionIgnoredException) {
            $this->penalizeProviderContributionInvalidToken($this->providerContributionInvalidTokenLimiter, $request);

            return $this->json(['error' => 'Invalid token'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException|ExchangeRateNotFoundException $e) {
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
