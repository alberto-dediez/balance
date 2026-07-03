<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class GetPhaseService
{
    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $authorizer,
        private ActivityCatalogProviderRegistry $catalogProviders,
        private ActivityRepository $activityRepository,
    ) {}

    public function __invoke(GetPhaseRequest $request, User $user): GetPhaseResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null === $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::View, $user);

        $year = $eventCarbonFootprint->calculationYear();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        $sections = [];

        foreach ($request->phase->sections() as $definition) {
            $provider = $this->catalogProviders->forActivityType($definition->activityType);

            $activityIds = $eventCarbonFootprint->activities()
                ->filterByPhase($request->phase)
                ->filterBySource($definition->source)
                ->activityIds();

            $activities = [];
            foreach ($activityIds as $activityId) {
                $activity = $this->activityRepository->findById($activityId);

                if (null === $activity || ActivityType::fromActivity($activity) !== $definition->activityType) {
                    continue;
                }

                $activities[] = $activity;
            }

            $sections[] = new PhaseSection(
                definition: $definition,
                activities: $activities,
                catalog: $provider->catalog($year, $emissionFactorGroupId),
                secondaryCatalog: $provider->secondaryCatalog(),
            );
        }

        return new GetPhaseResponse($sections);
    }
}
