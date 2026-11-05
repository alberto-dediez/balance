<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintKpi\View;

use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardResponse;
use App\CarbonFootprint\Application\CarbonFootprintKpi\TimeSeriesPoint;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariation;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use Symfony\Contracts\Translation\TranslatorInterface;

final class KpiDashboardView
{
    public function __construct(private TranslatorInterface $translator) {}

    /** @return array<string, mixed> */
    public function build(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        if (!$dashboard->hasData || null === $dashboard->inventory) {
            return [];
        }

        $inventory = $dashboard->inventory;

        return [
            'years' => array_map(static fn(TimeSeriesPoint $point): string => (string) $point->year, $dashboard->series),
            'scopeLabels' => $this->scopeLabels(),
            'scopeSeries' => $this->scopeSeries($dashboard->series),
            'scopeValues' => array_map(static fn(Scope $scope): float => round($inventory->scopeKg($scope) / 1000, 2), Scope::cases()),
            'contributionRows' => $this->contributionRows($dashboard),
            'contributionChart' => $this->contributionChart($dashboard),
            'gasRows' => $this->gasRows($dashboard),
            'indicatorCharts' => $this->indicatorCharts($dashboard->series),
            'evolutionRows' => $this->evolutionRows($dashboard),
            'evolutionIndicators' => $this->evolutionIndicators($dashboard->series),
            'variations' => $this->variations($dashboard),
            'variationCharts' => $this->variationCharts($dashboard),
            'comparisonChart' => $this->comparisonChart($dashboard),
            'allIndicators' => IntensityIndicator::cases(),
            'previousYear' => $dashboard->variationVsPrevious?->fromYear,
            'previousDeltaPercent' => $dashboard->variationVsPrevious?->deltaPercent(),
        ];
    }

    /** @return list<string> */
    private function scopeLabels(): array
    {
        return array_map(fn(Scope $scope): string => $this->translator->trans($scope->value, [], 'scope'), Scope::cases());
    }

    /**
     * @param list<TimeSeriesPoint> $series
     *
     * @return list<array{name: string, data: list<float>}>
     */
    private function scopeSeries(array $series): array
    {
        $chartSeries = [];

        foreach (Scope::cases() as $scope) {
            $chartSeries[] = [
                'name' => $this->translator->trans($scope->value, [], 'scope'),
                'data' => array_map(static fn(TimeSeriesPoint $point): float => round(($point->byScopeKg[$scope->value] ?? 0.0) / 1000, 2), $series),
            ];
        }

        return $chartSeries;
    }

    /** @return list<array{label: string, tonnes: float, percent: float, cumulativePercent: float}> */
    private function contributionRows(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $rows = [];

        foreach ($dashboard->inventory->contribution ?? [] as $row) {
            $rows[] = [
                'label' => sprintf('%s · %s', $this->translator->trans($row['scope']->value, [], 'scope'), $this->translator->trans($row['source']->value, [], 'source')),
                'tonnes' => $row['kg'] / 1000,
                'percent' => $row['percent'],
                'cumulativePercent' => $row['cumulativePercent'],
            ];
        }

        return $rows;
    }

    /** @return array{labels: list<string>, values: list<float>} */
    private function contributionChart(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $rows = $this->contributionRows($dashboard);

        return [
            'labels' => array_map(static fn(array $row): string => $row['label'], $rows),
            'values' => array_map(static fn(array $row): float => round($row['tonnes'], 2), $rows),
        ];
    }

    /** @return list<array{label: string, kg: float}> */
    private function gasRows(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $gas = $dashboard->inventory?->gas;

        if (null === $gas) {
            return [];
        }

        $rows = [
            ['label' => $this->translator->trans('CO₂ (incl. factores sin desglose por gas)'), 'kg' => $gas->co2],
            ['label' => 'CH₄', 'kg' => $gas->ch4],
            ['label' => 'N₂O', 'kg' => $gas->n2o],
            ['label' => $this->translator->trans('Refrigerantes'), 'kg' => $gas->refrigerants],
        ];

        if (0.0 !== $gas->remainder) {
            $rows[] = ['label' => $this->translator->trans('Resto sin desglosar'), 'kg' => $gas->remainder];
        }

        return $rows;
    }

    /**
     * @param list<TimeSeriesPoint> $series
     *
     * @return list<array{indicator: IntensityIndicator, categories: list<string>, values: list<float>}>
     */
    private function indicatorCharts(array $series): array
    {
        $charts = [];

        foreach (IntensityIndicator::cases() as $indicator) {
            $categories = [];
            $values = [];

            foreach ($series as $point) {
                if (!isset($point->indicators[$indicator->value])) {
                    continue;
                }

                $categories[] = (string) $point->year;
                $values[] = $point->indicators[$indicator->value]->kgCo2eqPerUnit;
            }

            if (count($values) >= 2) {
                $charts[] = ['indicator' => $indicator, 'categories' => $categories, 'values' => $values];
            }
        }

        return $charts;
    }

    /**
     * @param list<TimeSeriesPoint> $series
     *
     * @return list<IntensityIndicator>
     */
    private function evolutionIndicators(array $series): array
    {
        $indicators = [];

        foreach (IntensityIndicator::cases() as $indicator) {
            foreach ($series as $point) {
                if (isset($point->indicators[$indicator->value])) {
                    $indicators[] = $indicator;

                    break;
                }
            }
        }

        return $indicators;
    }

    /** @return list<array{point: TimeSeriesPoint, deltaVsPrevious: ?float, deltaVsBase: ?float}> */
    private function evolutionRows(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $baseTotal = null;
        foreach ($dashboard->series as $point) {
            if ($point->isBaseYear) {
                $baseTotal = $point->totalKg;
            }
        }

        $rows = [];
        $previousTotal = null;

        foreach ($dashboard->series as $point) {
            $rows[] = [
                'point' => $point,
                'deltaVsPrevious' => self::deltaPercent($previousTotal, $point->totalKg),
                'deltaVsBase' => $point->isBaseYear ? null : self::deltaPercent($baseTotal, $point->totalKg),
            ];
            $previousTotal = $point->totalKg;
        }

        return $rows;
    }

    /** @return list<array{label: string, variation: InterannualVariation}> */
    private function variations(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $variations = [];

        if (null !== $dashboard->variationVsPrevious) {
            $variations[] = [
                'label' => $this->translator->trans('vs. {year}', ['{year}' => (string) $dashboard->variationVsPrevious->fromYear]),
                'variation' => $dashboard->variationVsPrevious,
            ];
        }

        if (null !== $dashboard->variationVsBaseYear) {
            $variations[] = [
                'label' => $this->translator->trans('vs. año base ({year})', ['{year}' => (string) $dashboard->variationVsBaseYear->fromYear]),
                'variation' => $dashboard->variationVsBaseYear,
            ];
        }

        return $variations;
    }

    /** @return list<array{title: string, categories: list<string>, series: list<array{name: string, data: list<float>}>}> */
    private function variationCharts(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $charts = [];

        foreach ($this->variations($dashboard) as $row) {
            $variation = $row['variation'];

            if (null !== $variation->activityEffect) {
                $categories = [$this->translator->trans('Efecto actividad'), $this->translator->trans('Efecto eficiencia'), $this->translator->trans('Efecto factores')];
                $data = [$variation->activityEffect, $variation->efficiencyEffect, $variation->factorEffect];
            } else {
                $categories = [$this->translator->trans('Actividad y eficiencia'), $this->translator->trans('Efecto factores')];
                $data = [$variation->efficiencyEffect, $variation->factorEffect];
            }

            $charts[] = [
                'title' => $row['label'],
                'categories' => $categories,
                'series' => [[
                    'name' => $row['label'],
                    'data' => array_map(static fn(float $kg): float => round($kg / 1000, 2), $data),
                ]],
            ];
        }

        return $charts;
    }

    /** @return array{labels: list<string>, values: list<float>} */
    private function comparisonChart(GetCarbonFootprintKpiDashboardResponse $dashboard): array
    {
        $labels = [];
        $values = [];

        foreach ($dashboard->propertyComparison as $row) {
            $labels[] = $row->name;
            $values[] = $row->intensity;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private static function deltaPercent(?float $from, float $to): ?float
    {
        if (null === $from || 0.0 === $from) {
            return null;
        }

        return ($to - $from) / $from * 100;
    }
}
