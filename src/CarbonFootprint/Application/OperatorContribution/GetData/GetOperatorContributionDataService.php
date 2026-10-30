<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\GetData;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class GetOperatorContributionDataService
{
    public function __construct(
        private OperatorContributionAuthenticator $authenticator,
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetOperatorContributionDataRequest $request): GetOperatorContributionDataResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $this->authenticator->ensureEditable($this->authenticator->locateCompanyCarbonFootprint($authentication));

        $contribution = $authentication->contribution;

        if (!$contribution->hasConsent()) {
            throw new OperatorContributionConsentRequiredException();
        }

        return new GetOperatorContributionDataResponse(
            contribution: $contribution,
            scope1Sections: $this->sectionsBuilder->build($contribution, Scope::Scope1, $contribution->sectionsFor(Scope::Scope1))->sections,
            scope2Sections: $this->sectionsBuilder->build($contribution, Scope::Scope2, $contribution->sectionsFor(Scope::Scope2))->sections,
        );
    }
}
