<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjection;

final class MitigationPlanView
{
    /**
     * @param list<MitigationAction>         $actions
     * @param list<MitigationEmissionSource> $emissionSources
     *
     * @return array{
     *     actions: list<array{action: MitigationAction, effects: list<array{source: string, itemName: string, reduction: float, withoutEmissions: bool}>, expectedReduction: float}>,
     *     projection: array{baseYear: int, years: list<int>, rows: list<array{scope: string, source: string, itemName: string, values: list<float>}>, totals: list<float>, interannual: list<float|null>}|null,
     *     chart: array{categories: list<string>, values: list<float>}|null
     * }
     */
    public function build(array $actions, array $emissionSources, ?MitigationProjection $projection): array
    {
        $sourcesByKey = [];
        $derivedSourcesByOriginKey = [];
        foreach ($emissionSources as $emissionSource) {
            $sourcesByKey[$emissionSource->key()] = $emissionSource;

            if (null !== $emissionSource->originKey) {
                $derivedSourcesByOriginKey[$emissionSource->originKey][] = $emissionSource;
            }
        }

        return [
            'actions' => array_map(fn(MitigationAction $action): array => $this->actionRow($action, $sourcesByKey, $derivedSourcesByOriginKey), $actions),
            'projection' => null !== $projection ? $this->projection($projection) : null,
            'chart' => null !== $projection ? $this->chart($projection) : null,
        ];
    }

    /**
     * @param array<string, MitigationEmissionSource>       $sourcesByKey
     * @param array<string, list<MitigationEmissionSource>> $derivedSourcesByOriginKey
     *
     * @return array{action: MitigationAction, effects: list<array{source: string, itemName: string, reduction: float, withoutEmissions: bool}>, expectedReduction: float}
     */
    private function actionRow(MitigationAction $action, array $sourcesByKey, array $derivedSourcesByOriginKey): array
    {
        $effects = [];
        $expectedReduction = 0.0;

        foreach ($action->effects() as $effect) {
            $effectKey = $effect->source()->value . '|' . $effect->itemId();
            $emissionSource = $sourcesByKey[$effectKey] ?? null;

            $effects[] = [
                'source' => $effect->source()->value,
                'itemName' => null !== $emissionSource ? $emissionSource->itemName : $effect->itemName(),
                'reduction' => $effect->reduction(),
                'withoutEmissions' => null === $emissionSource,
            ];

            if (null !== $emissionSource) {
                $expectedReduction += $emissionSource->emissions * $effect->reduction();
            }

            foreach ($derivedSourcesByOriginKey[$effectKey] ?? [] as $derivedSource) {
                $expectedReduction += $derivedSource->emissions * $effect->reduction();
            }
        }

        return [
            'action' => $action,
            'effects' => $effects,
            'expectedReduction' => $expectedReduction,
        ];
    }

    /** @return array{baseYear: int, years: list<int>, rows: list<array{scope: string, source: string, itemName: string, values: list<float>}>, totals: list<float>, interannual: list<float|null>} */
    private function projection(MitigationProjection $projection): array
    {
        $rows = [];

        foreach ($projection->sources as $projectionSource) {
            $values = [];
            foreach ($this->yearsWithBase($projection) as $year) {
                $values[] = $projectionSource->expectedByYear[$year] ?? 0.0;
            }

            $rows[] = [
                'scope' => $projectionSource->emissionSource->scope->value,
                'source' => $projectionSource->emissionSource->source->value,
                'itemName' => $projectionSource->emissionSource->itemName,
                'values' => $values,
            ];
        }

        $totals = [];
        $interannual = [];
        foreach ($this->yearsWithBase($projection) as $year) {
            $totals[] = $projection->totalByYear[$year] ?? 0.0;
            $interannual[] = $projection->interannualReductionByYear[$year] ?? null;
        }

        return [
            'baseYear' => $projection->baseYear,
            'years' => $projection->years,
            'rows' => $rows,
            'totals' => $totals,
            'interannual' => $interannual,
        ];
    }

    /** @return array{categories: list<string>, values: list<float>} */
    private function chart(MitigationProjection $projection): array
    {
        $categories = [];
        $values = [];

        foreach ($this->yearsWithBase($projection) as $year) {
            $categories[] = (string) $year;
            $values[] = round($projection->totalByYear[$year] ?? 0.0);
        }

        return [
            'categories' => $categories,
            'values' => $values,
        ];
    }

    /** @return list<int> */
    private function yearsWithBase(MitigationProjection $projection): array
    {
        return [$projection->baseYear, ...$projection->years];
    }
}
