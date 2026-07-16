<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetResults;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetResultsService
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ActivityRepository $activityRepository,
    ) {}

    public function __invoke(GetResultsRequest $request, User $user): GetResultsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $emissionsBySource = [];
        foreach (Source::cases() as $source) {
            $emissionsBySource[$source->value] = new Emissions();
        }

        /** @var array<string, Emissions> $emissionsByPhase */
        $emissionsByPhase = [];
        /** @var array<string, array<string, Emissions>> $emissionsByPhaseAndSource */
        $emissionsByPhaseAndSource = [];
        foreach (Phase::cases() as $phase) {
            $emissionsByPhase[$phase->value] = new Emissions();

            $emissionsByPhaseAndSource[$phase->value] = [];
            foreach (Source::cases() as $source) {
                $emissionsByPhaseAndSource[$phase->value][$source->value] = new Emissions();
            }
        }

        /** @var array<string, array{0: Phase, 1: Source}> $references */
        $references = [];
        $activityIds = [];
        foreach ($eventCarbonFootprint->activities()->toArray() as $footprintActivity) {
            $activityIds[] = $footprintActivity->activityId();
            $references[(string) $footprintActivity->activityId()] = [$footprintActivity->phase(), $footprintActivity->source()];
        }

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $key = (string) $activity->id();

            if (!isset($references[$key])) {
                continue;
            }

            [$phase, $source] = $references[$key];

            $emissionsBySource[$source->value] = $emissionsBySource[$source->value]->add($activity->emissions());
            $emissionsByPhase[$phase->value] = $emissionsByPhase[$phase->value]->add($activity->emissions());
            $emissionsByPhaseAndSource[$phase->value][$source->value] = $emissionsByPhaseAndSource[$phase->value][$source->value]->add($activity->emissions());
        }

        return new GetResultsResponse(
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
            emissionsBySource: $emissionsBySource,
            emissionsByPhase: $emissionsByPhase,
            emissionsByPhaseAndSource: $emissionsByPhaseAndSource,
            exclusions: $eventCarbonFootprint->exclusions()->toArray(),
        );
    }
}
