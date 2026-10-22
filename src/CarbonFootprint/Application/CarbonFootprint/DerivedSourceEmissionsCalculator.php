<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class DerivedSourceEmissionsCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
    ) {}

    /** @return array<string, Emissions> */
    public function calculate(CarbonFootprint $carbonFootprint, CalculationStage $stage): array
    {
        $emissions = [];

        foreach ($this->derivedSources($stage) as $source) {
            $emissions[$source->value] = new Emissions();
        }

        foreach ($this->lines($carbonFootprint, $stage) as $line) {
            $emissions[$line->source->value] = ($emissions[$line->source->value] ?? new Emissions())->add($line->emissions);
        }

        return $emissions;
    }

    /** @return list<DerivedSourceLine> */
    public function lines(CarbonFootprint $carbonFootprint, CalculationStage $stage): array
    {
        if (!in_array(Source::FuelAndEnergyRelated, $this->derivedSources($stage), true)
            || !$this->isCalculated($carbonFootprint, $stage, Source::FuelAndEnergyRelated)) {
            return [];
        }

        $lines = [];

        foreach ($this->fuelAndEnergyActivities($carbonFootprint) as [$activity, $placement]) {
            $emissions = $this->wellToTankEmissions($activity);

            if (null === $emissions || (float) $emissions->co2eq() <= 0.0) {
                continue;
            }

            $lines[] = new DerivedSourceLine(
                source: Source::FuelAndEnergyRelated,
                origin: $placement,
                activityType: ActivityType::fromActivity($activity),
                emissions: $emissions,
                activityId: $activity->id(),
            );
        }

        return $lines;
    }

    /** @return list<Source> */
    private function derivedSources(CalculationStage $stage): array
    {
        return $stage instanceof Scope ? $stage->derivedSources() : [];
    }

    private function isCalculated(CarbonFootprint $carbonFootprint, CalculationStage $stage, Source $source): bool
    {
        return !$carbonFootprint->isStageExcluded($stage)
            && !array_key_exists($source->value, $carbonFootprint->exclusionsFor($stage));
    }

    /** @return list<array{Activity, ActivityPlacement}> */
    private function fuelAndEnergyActivities(CarbonFootprint $carbonFootprint): array
    {
        /** @var array<string, ActivityPlacement> $placements */
        $placements = [];
        $activityIds = [];
        foreach ($carbonFootprint->activityReferences() as $reference) {
            $stageKey = $reference->placement->stage->key();

            if (Scope::Scope1->key() !== $stageKey && Scope::Scope2->key() !== $stageKey) {
                continue;
            }

            $activityIds[] = $reference->activityId;
            $placements[(string) $reference->activityId] = $reference->placement;
        }

        if ([] === $activityIds) {
            return [];
        }

        $activities = [];
        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $placement = $placements[(string) $activity->id()] ?? null;

            if (null === $placement || null === $this->wellToTankEmissions($activity)) {
                continue;
            }

            $activities[] = [$activity, $placement];
        }

        return $activities;
    }

    private function wellToTankEmissions(Activity $activity): ?Emissions
    {
        return match (true) {
            $activity instanceof CombustibleActivity,
            $activity instanceof MachineryFuelActivity,
            $activity instanceof ElectricityActivity => $activity->wellToTankEmissions(),
            default => null,
        };
    }
}
