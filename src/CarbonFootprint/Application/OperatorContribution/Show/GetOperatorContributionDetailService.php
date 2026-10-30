<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\Show;

use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionAuthorizer;
use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetOperatorContributionDetailService
{
    public function __construct(
        private OperatorContributionRepository $contributionRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
        private OperatorContributionAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
        private OperatorContributionEmissionsCalculator $emissionsCalculator,
        private PropertyRepository $propertyRepository,
    ) {}

    public function __invoke(GetOperatorContributionDetailRequest $request, User $user): GetOperatorContributionDetailResponse
    {
        $contribution = $this->contributionRepository->find($request->contributionId);
        $companyCarbonFootprintId = $contribution?->companyCarbonFootprintId();
        $companyCarbonFootprint = $companyCarbonFootprintId instanceof CompanyCarbonFootprintId ? $this->companyCarbonFootprintRepository->find($companyCarbonFootprintId) : null;

        if (null === $contribution || null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('OperatorContribution not found');
        }

        $this->companyCarbonFootprintAuthorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);
        $this->authorizer->denyUnlessCanDoThat($contribution, SecurityAction::View, $user);

        $propertyId = $contribution->propertyId();

        return new GetOperatorContributionDetailResponse(
            contribution: $contribution,
            property: $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null,
            scope1Sections: $this->sectionsBuilder->build($contribution, Scope::Scope1, $contribution->sectionsFor(Scope::Scope1))->sections,
            scope2Sections: $this->sectionsBuilder->build($contribution, Scope::Scope2, $contribution->sectionsFor(Scope::Scope2))->sections,
            summary: $this->emissionsCalculator->summary($contribution),
        );
    }
}
