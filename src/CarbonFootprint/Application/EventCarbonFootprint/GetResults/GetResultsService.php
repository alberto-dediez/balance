<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetResultsService
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private EmissionsBreakdownBuilder $breakdownBuilder,
    ) {}

    public function __invoke(GetResultsRequest $request, User $user): GetResultsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $breakdown = $this->breakdownBuilder->build($eventCarbonFootprint, Phase::cases());

        return new GetResultsResponse(
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
            marketBasedElectricityEmissions: $breakdown->marketBasedElectricity,
            locationBasedElectricityEmissions: $breakdown->locationBasedElectricity,
            locationBasedTotalEmissions: $breakdown->locationBasedTotal,
            electricityActivities: $breakdown->electricityActivities,
            electricityActivitiesWithoutLocationBased: $breakdown->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $breakdown->bySource,
            emissionsByPhase: $breakdown->byStage,
            emissionsByPhaseAndSource: $breakdown->byStageAndSource,
            exclusions: $eventCarbonFootprint->allExclusions(),
        );
    }
}
