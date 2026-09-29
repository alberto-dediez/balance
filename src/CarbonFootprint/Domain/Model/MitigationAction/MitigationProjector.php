<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

final class MitigationProjector
{
    /**
     * @param list<MitigationEmissionSource> $emissionSources
     * @param list<MitigationAction>         $actions
     */
    public function project(int $footprintYear, array $emissionSources, array $actions): ?MitigationProjection
    {
        $emissionSources = $this->sorted($emissionSources);

        $emittingKeys = [];
        foreach ($emissionSources as $emissionSource) {
            if ($emissionSource->emissions > 0.0) {
                $emittingKeys[$emissionSource->key()] = true;
            }
        }

        $targetYear = null;
        $ratesByKey = [];

        foreach ($actions as $action) {
            $years = $action->targetYear() - $action->baseYear();

            if ($years <= 0) {
                continue;
            }

            foreach ($action->effects() as $effect) {
                $key = $effect->source()->value . '|' . $effect->itemId();

                if (!isset($emittingKeys[$key])) {
                    continue;
                }

                $targetYear = max($targetYear ?? $action->targetYear(), $action->targetYear());
                $ratesByKey[$key][] = [
                    'rate' => 1.0 - (1.0 - $effect->reduction()) ** (1.0 / $years),
                    'baseYear' => $action->baseYear(),
                    'targetYear' => $action->targetYear(),
                ];
            }
        }

        if (null === $targetYear || $targetYear <= $footprintYear) {
            return null;
        }

        $years = range($footprintYear + 1, $targetYear);
        $sources = [];
        $totalByYear = array_fill_keys(array_merge([$footprintYear], $years), 0.0);

        foreach ($emissionSources as $emissionSource) {
            $expected = $emissionSource->emissions;
            $rateByYear = [];
            $reductionByYear = [];
            $expectedByYear = [$footprintYear => $expected];
            $hasReductions = false;

            foreach ($years as $year) {
                $rate = 0.0;

                foreach ($ratesByKey[$emissionSource->key()] ?? [] as $entry) {
                    if ($entry['baseYear'] < $year && $year <= $entry['targetYear']) {
                        $rate += $entry['rate'];
                    }
                }

                $rate = min(1.0, $rate);
                $next = $expected * (1.0 - $rate);

                $rateByYear[$year] = $rate;
                $reductionByYear[$year] = $expected - $next;
                $expectedByYear[$year] = $next;
                $hasReductions = $hasReductions || $rate > 0.0;
                $expected = $next;
            }

            foreach ($expectedByYear as $year => $value) {
                $totalByYear[$year] += $value;
            }

            $sources[] = new MitigationProjectionSource(
                emissionSource: $emissionSource,
                rateByYear: $rateByYear,
                reductionByYear: $reductionByYear,
                expectedByYear: $expectedByYear,
                hasReductions: $hasReductions,
            );
        }

        $interannualReductionByYear = [];
        foreach ($years as $year) {
            $previous = $totalByYear[$year - 1];
            $interannualReductionByYear[$year] = 0.0 === $previous ? 0.0 : ($previous - $totalByYear[$year]) / $previous;
        }

        return new MitigationProjection(
            baseYear: $footprintYear,
            targetYear: $targetYear,
            years: $years,
            sources: $sources,
            totalByYear: $totalByYear,
            interannualReductionByYear: $interannualReductionByYear,
        );
    }

    /**
     * @param list<MitigationEmissionSource> $emissionSources
     *
     * @return list<MitigationEmissionSource>
     */
    private function sorted(array $emissionSources): array
    {
        $scopes = array_flip(array_map(static fn(Scope $scope): string => $scope->value, Scope::cases()));
        $sources = array_flip(array_map(static fn(Source $source): string => $source->value, Source::cases()));

        usort(
            $emissionSources,
            static fn(MitigationEmissionSource $a, MitigationEmissionSource $b): int => [$scopes[$a->scope->value], $sources[$a->source->value], $a->itemName]
                <=> [$scopes[$b->scope->value], $sources[$b->source->value], $b->itemName],
        );

        return $emissionSources;
    }
}
