<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Provider\ProviderId;

final class ProviderContributionPhaseSectionsBuilder
{
    public function __construct(
        private ActivityCatalogProviderRegistry $catalogProviders,
        private ActivityRepository $activityRepository,
    ) {}

    /** @return list<PhaseSection> */
    public function build(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, ProviderId $providerId): array
    {
        $year = $eventCarbonFootprint->calculationYear();
        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();

        $sections = [];

        foreach ($phase->providerSections() as $definition) {
            $provider = $this->catalogProviders->forActivityType($definition->activityType);

            $activityIds = $eventCarbonFootprint->activities()
                ->filterByPhase($phase)
                ->filterBySource($definition->source)
                ->filterByProviderId($providerId)
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
                secondaryCatalog: $provider->secondaryCatalog($year, $emissionFactorGroupId),
            );
        }

        return $sections;
    }
}
