<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetPropertyCarbonFootprintResultsService
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private EmissionsBreakdownBuilder $breakdownBuilder,
    ) {}

    public function __invoke(GetPropertyCarbonFootprintResultsRequest $request, User $user): GetPropertyCarbonFootprintResultsResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::View, $user);

        $breakdown = $this->breakdownBuilder->build($propertyCarbonFootprint, Scope::cases());

        return new GetPropertyCarbonFootprintResultsResponse(
            totalEmissions: $breakdown->total,
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            emissionsBySource: $breakdown->bySource,
            emissionsByScope: $breakdown->byStage,
            emissionsByScopeAndSource: $breakdown->byStageAndSource,
            exclusions: $propertyCarbonFootprint->allExclusions(),
        );
    }
}
