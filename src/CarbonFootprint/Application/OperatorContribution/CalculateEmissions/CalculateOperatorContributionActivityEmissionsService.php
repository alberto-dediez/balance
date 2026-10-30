<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\CalculateEmissions;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;

final class CalculateOperatorContributionActivityEmissionsService
{
    public function __construct(
        private OperatorContributionAuthenticator $authenticator,
        private ActivityEmissionsResolver $emissionsResolver,
    ) {}

    public function __invoke(CalculateOperatorContributionActivityEmissionsRequest $request): CalculateActivityEmissionsResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);

        return $this->emissionsResolver->resolve(
            $authentication->contribution,
            $request->activityType,
            $request->itemId,
            $request->condition,
            $request->quantity,
            $request->secondaryItemId,
        );
    }
}
