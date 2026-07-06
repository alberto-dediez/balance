<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;

class EmissionsBreakdownCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {}

    /**
     * @param EventCarbonFootprint[] $footprints
     */
    public function __invoke(array $footprints): EmissionsBreakdown
    {
        /** @var ActivityId[] $activityIds */
        $activityIds = [];

        /** @var array<string, array{Phase, Source}> $references */
        $references = [];

        foreach ($footprints as $footprint) {
            foreach ($footprint->activities()->toArray() as $footprintActivity) {
                $activityIds[] = $footprintActivity->activityId();
                $references[(string) $footprintActivity->activityId()] = [$footprintActivity->phase(), $footprintActivity->source()];
            }
        }

        $emissionsByPhase = [];
        $emissionsBySource = [];

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $co2eq = (float) $activity->emissions()->co2eq();

            if ($co2eq <= 0.0 || !isset($references[(string) $activity->id()])) {
                continue;
            }

            [$phase, $source] = $references[(string) $activity->id()];

            $emissionsByPhase[$phase->value] = ($emissionsByPhase[$phase->value] ?? 0.0) + $co2eq;
            $emissionsBySource[$source->value] = ($emissionsBySource[$source->value] ?? 0.0) + $co2eq;
        }

        $orderedByPhase = [];
        foreach (Phase::cases() as $phase) {
            if (isset($emissionsByPhase[$phase->value])) {
                $orderedByPhase[$phase->value] = $emissionsByPhase[$phase->value];
            }
        }

        arsort($emissionsBySource);

        return new EmissionsBreakdown(byPhase: $orderedByPhase, bySource: $emissionsBySource);
    }
}
