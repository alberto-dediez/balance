<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferences;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\GasBreakdown;

class GasBreakdownCalculator
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private CountedActivityReferences $countedActivityReferences,
    ) {}

    public function calculate(CarbonFootprint $carbonFootprint, Gwp $gwp, Emissions $total): GasBreakdown
    {
        /** @var array<string, Source> $sources */
        $sources = [];
        $activityIds = [];
        foreach ($this->countedActivityReferences->of($carbonFootprint) as $reference) {
            $activityIds[] = $reference->activityId;
            $sources[(string) $reference->activityId] = $reference->placement->source;
        }

        $co2 = 0.0;
        $ch4 = 0.0;
        $n2o = 0.0;
        $refrigerants = 0.0;

        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $source = $sources[(string) $activity->id()] ?? null;

            if (null === $source) {
                continue;
            }

            $emissions = $activity->emissions();

            if (Source::RefrigerantLeaks === $source) {
                $refrigerants += (float) $emissions->co2eq();

                continue;
            }

            $co2 += (float) $emissions->co2() * $gwp->gwpCo2();
            $ch4 += (float) $emissions->ch4() / 1000 * $gwp->gwpCh4();
            $n2o += (float) $emissions->n2o() / 1000 * $gwp->gwpN2O();
        }

        return GasBreakdown::fromComponents($co2, $ch4, $n2o, $refrigerants, (float) $total->co2eq());
    }
}
