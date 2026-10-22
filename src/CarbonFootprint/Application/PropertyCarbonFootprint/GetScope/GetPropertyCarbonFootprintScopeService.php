<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetScope;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
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
}
