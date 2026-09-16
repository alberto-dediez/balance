<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetCompanyCarbonFootprintResultsService
{
    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyRepository $propertyRepository,
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

        $companyId = $companyCarbonFootprint->companyId();
        $properties = null !== $companyId ? array_values($this->propertyRepository->findByCompanyId($companyId)) : [];

        $breakdown = $this->breakdownBuilder->build($companyCarbonFootprint, Scope::cases());

        return new GetCompanyCarbonFootprintResultsResponse(
            totalEmissions: $breakdown->total,
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            emissionsByPropertyId: $breakdown->byPropertyId,
            properties: $properties,
            exclusions: $companyCarbonFootprint->allExclusions(),
        );
    }
}
