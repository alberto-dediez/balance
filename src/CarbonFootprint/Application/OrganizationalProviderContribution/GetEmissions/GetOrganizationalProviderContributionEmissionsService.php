<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\GetEmissions;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class GetOrganizationalProviderContributionEmissionsService
{
    public function __construct(
        private OrganizationalProviderContributionAuthenticator $authenticator,
        private StageSectionsBuilder $sectionsBuilder,
    ) {}

    public function __invoke(GetOrganizationalProviderContributionEmissionsRequest $request): GetOrganizationalProviderContributionEmissionsResponse
    {
        $authentication = $this->authenticator->authenticate($request->token);
        $contribution = $authentication->contribution;
        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            throw new OrganizationalProviderContributionTokenInvalidException();
        }

        $located = $this->authenticator->locateCarbonFootprint($authentication);
        $located->ensureIsEditable();
        $carbonFootprint = $located->carbonFootprint;

        $sectionsBySource = [];
        foreach ($contribution->sources() as $source) {
            $sectionsBySource[$source->value] = $this->sectionsBuilder->build(
                $carbonFootprint,
                Scope::Scope3,
                Scope::Scope3->providerSectionsFor([$source]),
                $providerId,
                false,
                $contribution->propertyId(),
            )->sections;
        }

        return new GetOrganizationalProviderContributionEmissionsResponse($contribution, $sectionsBySource);
    }
}
