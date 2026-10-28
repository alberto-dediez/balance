<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\List;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionSummary;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\ProviderContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class ListOrganizationalProviderContributionsService
{
    public function __construct(
        private OrganizationalCarbonFootprintLocator $locator,
        private OrganizationalProviderContributionRepository $contributionRepository,
        private ProviderRepository $providerRepository,
        private PropertyRepository $propertyRepository,
        private ProviderContributionEmissionsCalculator $emissionsCalculator,
    ) {}

    public function __invoke(ListOrganizationalProviderContributionsRequest $request, User $user): ListOrganizationalProviderContributionsResponse
    {
        $located = $this->locator->locate($request->subjectType, $request->carbonFootprintId);

        $located->denyUnlessCanDoThat(SecurityAction::View, $user);

        $summaries = [];
        foreach ($this->contributionRepository->findActiveByFootprint($request->subjectType, $request->carbonFootprintId) as $contribution) {
            $providerId = $contribution->providerId();
            $propertyId = $contribution->propertyId();
            $emissionsBySource = $this->emissionsCalculator->emissionsBySource($located->carbonFootprint, $contribution);

            $summaries[] = new OrganizationalProviderContributionSummary(
                contribution: $contribution,
                provider: null !== $providerId ? $this->providerRepository->find($providerId) : null,
                property: null !== $propertyId ? $this->propertyRepository->find($propertyId) : null,
                emissionsBySource: $emissionsBySource,
                total: $this->emissionsCalculator->total($emissionsBySource),
            );
        }

        return new ListOrganizationalProviderContributionsResponse($summaries);
    }
}
