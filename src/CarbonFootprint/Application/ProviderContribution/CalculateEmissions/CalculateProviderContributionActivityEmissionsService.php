<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;

final class CalculateProviderContributionActivityEmissionsService
{
    public function __construct(
        private ProviderContributionAuthenticator $authenticator,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculateProviderContributionActivityEmissionsRequest $request): CalculateActivityEmissionsResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $providerContribution = $authentication->providerContribution;

        $eventCarbonFootprintId = $providerContribution->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;

        if (null === $eventCarbonFootprint) {
            throw new ProviderContributionTokenInvalidException();
        }

        return $this->emissionsResolver->resolve(
            $eventCarbonFootprint,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }
}
