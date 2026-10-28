<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;

final class CalculateOrganizationalProviderContributionActivityEmissionsService
{
    public function __construct(
        private OrganizationalProviderContributionAuthenticator $authenticator,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculateOrganizationalProviderContributionActivityEmissionsRequest $request): CalculateActivityEmissionsResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $carbonFootprint = $this->authenticator->locateCarbonFootprint($authentication)->carbonFootprint;

        return $this->emissionsResolver->resolve(
            $carbonFootprint,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }
}
