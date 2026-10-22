<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class EmissionsBreakdownBuilder
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
    ) {}

    /** @param list<CalculationStage> $stages */
    public function build(CarbonFootprint $carbonFootprint, array $stages): EmissionsBreakdown
    {
        /** @var array<string, Emissions> $bySource */
        $bySource = [];
        foreach (Source::cases() as $source) {
            $bySource[$source->value] = new Emissions();
        }

        /** @var array<string, Emissions> $byStage */
        $byStage = [];
        /** @var array<string, array<string, Emissions>> $byStageAndSource */
        $byStageAndSource = [];
        foreach ($stages as $stage) {
            $byStage[$stage->key()] = new Emissions();

            $byStageAndSource[$stage->key()] = [];
            foreach (Source::cases() as $source) {
                $byStageAndSource[$stage->key()][$source->value] = new Emissions();
            }
        }

        /** @var array<string, ActivityPlacement> $placements */
        $placements = [];
        $activityIds = [];
        foreach ($carbonFootprint->activityReferences() as $reference) {
            $activityIds[] = $reference->activityId;
            $placements[(string) $reference->activityId] = $reference->placement;
        }

        $total = new Emissions();
        /** @var array<string, Emissions> $byPropertyId */
        $byPropertyId = [];
        $locationBasedElectricity = new Emissions();

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $key = (string) $activity->id();

            if (!isset($placements[$key])) {
                continue;
            }

            $placement = $placements[$key];
            $stageKey = $placement->stage->key();
            $sourceValue = $placement->source->value;
            $propertyKey = (string) $placement->propertyId;

            $total = $total->add($activity->emissions());
            $bySource[$sourceValue] = $bySource[$sourceValue]->add($activity->emissions());

            if (isset($byStage[$stageKey])) {
                $byStage[$stageKey] = $byStage[$stageKey]->add($activity->emissions());
                $byStageAndSource[$stageKey][$sourceValue] = $byStageAndSource[$stageKey][$sourceValue]->add($activity->emissions());
            }

            $byPropertyId[$propertyKey] = ($byPropertyId[$propertyKey] ?? new Emissions())->add($activity->emissions());

            if ($activity instanceof ElectricityActivity) {
                $locationBasedEmissions = $activity->locationBasedEmissions();

                if (null !== $locationBasedEmissions) {
                    $locationBasedElectricity = $locationBasedElectricity->add($locationBasedEmissions);
                }
            }
        }

        foreach ($stages as $stage) {
            if (!$stage instanceof Scope || [] === $stage->derivedSources()) {
                continue;
            }

            foreach ($this->derivedSourceEmissionsCalculator->lines($carbonFootprint, $stage) as $line) {
                $sourceValue = $line->source->value;
                $propertyKey = (string) $line->origin->propertyId;

                $total = $total->add($line->emissions);
                $bySource[$sourceValue] = $bySource[$sourceValue]->add($line->emissions);
                $byStage[$stage->key()] = $byStage[$stage->key()]->add($line->emissions);
                $byStageAndSource[$stage->key()][$sourceValue] = $byStageAndSource[$stage->key()][$sourceValue]->add($line->emissions);
                $byPropertyId[$propertyKey] = ($byPropertyId[$propertyKey] ?? new Emissions())->add($line->emissions);
            }
        }

        return new EmissionsBreakdown(
            total: $total,
            bySource: $bySource,
            byStage: $byStage,
            byStageAndSource: $byStageAndSource,
            byPropertyId: $byPropertyId,
            locationBasedElectricity: $locationBasedElectricity,
        );
    }
}
