<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\List\ListOrganizationalProviderContributionsService;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionSummary;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetPropertyCarbonFootprintScopeService
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private StageSectionsBuilder $sectionsBuilder,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private ListOrganizationalProviderContributionsService $listProviderContributions,
    ) {}

    public function __invoke(GetPropertyCarbonFootprintScopeRequest $request, User $user): GetPropertyCarbonFootprintScopeResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::View, $user);

        $stageSections = $this->sectionsBuilder->build($propertyCarbonFootprint, $request->scope);

        return new GetPropertyCarbonFootprintScopeResponse(
            sections: $stageSections->sections,
            exclusions: $stageSections->exclusions,
            stageExclusion: $this->stageExclusion($propertyCarbonFootprint, $request->scope),
            derivedEmissions: $this->derivedSourceEmissionsCalculator->calculate($propertyCarbonFootprint, $request->scope),
            derivedLines: $this->derivedSourceEmissionsCalculator->lines($propertyCarbonFootprint, $request->scope),
            providerContributions: $this->providerContributions($request->scope, (string) $request->propertyCarbonFootprintId, $user),
        );
    }

    private function stageExclusion(PropertyCarbonFootprint $propertyCarbonFootprint, Scope $scope): ?StageExclusionAnswer
    {
        $reason = $propertyCarbonFootprint->stageExclusionReason($scope);

        if (!$propertyCarbonFootprint->isStageExcluded($scope) || null === $reason) {
            return null;
        }

        return new StageExclusionAnswer($reason, $propertyCarbonFootprint->stageExclusionComment($scope));
    }

    /** @return list<OrganizationalProviderContributionSummary> */
    private function providerContributions(Scope $scope, string $carbonFootprintId, User $user): array
    {
        if (Scope::Scope3 !== $scope) {
            return [];
        }

        return ($this->listProviderContributions)(
            new ListOrganizationalProviderContributionsRequest(CarbonFootprintReportSubjectType::Property, $carbonFootprintId),
            $user,
        )->contributions;
    }
}
