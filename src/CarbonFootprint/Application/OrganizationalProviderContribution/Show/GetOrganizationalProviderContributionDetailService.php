<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocator;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetOrganizationalProviderContributionDetailService
{
    public function __construct(
        private OrganizationalProviderContributionRepository $contributionRepository,
        private OrganizationalProviderContributionAuthorizer $authorizer,
        private OrganizationalCarbonFootprintLocator $locator,
        private StageSectionsBuilder $sectionsBuilder,
        private ProviderRepository $providerRepository,
        private PropertyRepository $propertyRepository,
    ) {}

    public function __invoke(GetOrganizationalProviderContributionDetailRequest $request, User $user): GetOrganizationalProviderContributionDetailResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);

        if (null === $contribution) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::View, $user);

        $located = $this->locator->locate($contribution->subjectType(), $contribution->carbonFootprintId());

        $located->denyUnlessCanDoThat(SecurityAction::View, $user);

        $providerId = $contribution->providerId();

        if (!$providerId instanceof ProviderId) {
            throw new \InvalidArgumentException('OrganizationalProviderContribution has no provider');
        }

        $sectionsBySource = [];
        foreach ($contribution->sources() as $source) {
            $sectionsBySource[$source->value] = $this->sectionsBuilder->build(
                $located->carbonFootprint,
                Scope::Scope3,
                Scope::Scope3->providerSectionsFor([$source]),
                $providerId,
                false,
                $contribution->propertyId(),
            )->sections;
        }

        $propertyId = $contribution->propertyId();

        return new GetOrganizationalProviderContributionDetailResponse(
            contribution: $contribution,
            carbonFootprint: $located->carbonFootprint,
            provider: $this->providerRepository->find($providerId),
            property: null !== $propertyId ? $this->propertyRepository->find($propertyId) : null,
            sectionsBySource: $sectionsBySource,
        );
    }
}
