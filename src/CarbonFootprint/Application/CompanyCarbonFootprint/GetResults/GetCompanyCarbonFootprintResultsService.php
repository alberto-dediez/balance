<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetCompanyCarbonFootprintResultsService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyProperties $companyProperties,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private EmissionsBreakdownBuilder $breakdownBuilder,
    ) {}

    public function __invoke(GetCompanyCarbonFootprintResultsRequest $request, User $user): GetCompanyCarbonFootprintResultsResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::View, $user);

        $properties = $this->companyProperties->all($companyCarbonFootprint->companyId());

        $breakdown = $this->breakdownBuilder->build($companyCarbonFootprint, Scope::cases());

        return new GetCompanyCarbonFootprintResultsResponse(
            totalEmissions: $breakdown->total,
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            locationBasedTotalEmissions: $breakdown->locationBasedTotal,
            electricityActivitiesWithoutLocationBased: $breakdown->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            emissionsByPropertyId: $breakdown->byPropertyId,
            properties: $properties,
            exclusions: $companyCarbonFootprint->allExclusions(),
            scope3Exclusion: $this->scope3Exclusion($companyCarbonFootprint),
        );
    }

    private function scope3Exclusion(CompanyCarbonFootprint $companyCarbonFootprint): ?StageExclusionAnswer
    {
        $reason = $companyCarbonFootprint->stageExclusionReason(Scope::Scope3);

        if (!$companyCarbonFootprint->isStageExcluded(Scope::Scope3) || null === $reason) {
            return null;
        }

        return new StageExclusionAnswer($reason, $companyCarbonFootprint->stageExclusionComment(Scope::Scope3));
    }
}
