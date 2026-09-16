<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;

final class StageSectionsBuilder
{
    public function __construct(
        private ActivityCatalogProviderRegistry $catalogProviders,
        private ActivityRepository $activityRepository,
    ) {}

    /** @param ?list<SectionDefinition> $sections */
    public function build(CarbonFootprint $carbonFootprint, CalculationStage $stage, ?array $sections = null, ?ProviderId $providerId = null, bool $fromEmployees = false, ?PropertyId $propertyId = null): StageSections
    {
        $sections ??= $stage->sections();

        $year = $carbonFootprint->calculationYear();
        $emissionFactorGroupId = $carbonFootprint->emissionFactorGroupId();

        $stageSections = [];

        foreach ($sections as $definition) {
            $provider = $this->catalogProviders->forActivityType($definition->activityType);

            $activityIds = $carbonFootprint->activityIds(new ActivityPlacement(
                stage: $stage,
                source: $definition->source,
                providerId: $providerId,
                fromEmployees: $fromEmployees,
                propertyId: $propertyId,
            ));

            $activities = [];
            foreach ($activityIds as $activityId) {
                $activity = $this->activityRepository->findById($activityId);

                if (null === $activity || ActivityType::fromActivity($activity) !== $definition->activityType) {
                    continue;
                }

                $activities[] = $activity;
            }

            $stageSections[] = new StageSection(
                definition: $definition,
                activities: $activities,
                catalog: $provider->catalog($year, $emissionFactorGroupId),
                secondaryCatalog: $provider->secondaryCatalog($year, $emissionFactorGroupId),
            );
        }

        return new StageSections($stageSections, $carbonFootprint->exclusionsFor($stage));
    }
}
