<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Chart;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Chart\ApexChartClient;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApexChartCarbonFootprintReportChartsGenerator implements CarbonFootprintReportChartsGenerator
{
    private const string COLOR_TEAL = '#3F738D';
    private const string COLOR_LIME = '#C2D500';
    private const string COLOR_FALLBACK = '#C46100';

    /** @var list<string> */
    private const array PALETTE = ['#004B95', '#38812F', '#009596', '#8481DD', '#F0AB00'];

    public function __construct(
        private readonly ApexChartClient $chartClient,
        private readonly TranslatorInterface $translator,
    ) {}

    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportCharts
    {
        return match (true) {
            $data instanceof EventCarbonFootprintReportData => $this->generateForEvent($data, $locale),
            $data instanceof OrganizationalCarbonFootprintReportData => $this->generateForOrganization($data, $locale),
            default => throw new \InvalidArgumentException(sprintf('Unsupported report data "%s".', $data::class)),
        };
    }

    private function generateForEvent(EventCarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportCharts
    {
        $images = [
            'bySource' => $this->generateBySource($data->emissionsBySource, $locale),
            'byPhase' => $this->generateByPhase($data, $locale),
            'byPhaseAndSource' => $this->generateByPhaseAndSource($data, $locale),
        ];

        $compensation = $this->generateCompensation($data, $locale);

        if (null !== $compensation) {
            $images['compensation'] = $compensation;
        }

        return new CarbonFootprintReportCharts($images);
    }

    private function generateForOrganization(OrganizationalCarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportCharts
    {
        $images = [
            'bySource' => $this->generateBySource($data->emissionsBySource, $locale),
            'byScope' => $this->generateByScope($data, $locale),
        ];

        if ([] !== $data->emissionsByProperty) {
            $images['byProperty'] = $this->generateByProperty($data, $locale);
        }

        return new CarbonFootprintReportCharts($images);
    }

    private function generateCompensation(EventCarbonFootprintReportData $data, Locale $locale): ?string
    {
        $compensation = $data->compensation;

        if (null === $compensation || !$compensation->hasCompensations()) {
            return null;
        }

        $emitted = (float) $data->totalEmissions->co2eq();
        $compensated = min($compensation->totalTonnes * 1000, $emitted);

        return $this->chartClient->generate([
            'chart' => ['type' => 'donut', 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'series' => [round($emitted - $compensated, 0), round($compensated, 0)],
            'labels' => [
                $this->translator->trans('Sin compensar', [], 'messages', $locale->value),
                $this->translator->trans('Compensado', [], 'messages', $locale->value),
            ],
            'colors' => [self::COLOR_FALLBACK, self::COLOR_LIME],
            'legend' => ['show' => true],
        ]);
    }

    /** @param array<string, Emissions> $emissionsBySource */
    private function generateBySource(array $emissionsBySource, Locale $locale): string
    {
        $labels = [];
        $values = [];
        $colors = [];

        $paletteIndex = 0;
        foreach (Source::cases() as $source) {
            $co2eq = (float) ($emissionsBySource[$source->value] ?? new Emissions())->co2eq();

            if ($co2eq <= 0.0) {
                continue;
            }

            $labels[] = $this->translator->trans($source->value, [], 'source', $locale->value);
            $values[] = round($co2eq, 0);
            $colors[] = self::PALETTE[$paletteIndex % count(self::PALETTE)];
            ++$paletteIndex;
        }

        if ([] === $values) {
            $values = [1];
            $labels = ['N/A'];
            $colors = [self::COLOR_FALLBACK];
        }

        return $this->chartClient->generate([
            'chart' => ['type' => 'donut', 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'series' => $values,
            'labels' => $labels,
            'colors' => $colors,
            'legend' => ['show' => true],
        ]);
    }

    private function generateByPhase(EventCarbonFootprintReportData $data, Locale $locale): string
    {
        $categories = [];
        $values = [];

        foreach (Phase::cases() as $phase) {
            $categories[] = $this->translator->trans($phase->value, [], 'phase', $locale->value);
            $values[] = round((float) $data->emissionsByPhase[$phase->value]->co2eq(), 0);
        }

        return $this->chartClient->generate([
            'chart' => ['type' => 'bar', 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'series' => [['name' => 'CO2eq', 'data' => $values]],
            'xaxis' => ['categories' => $categories],
            'colors' => [self::COLOR_TEAL],
        ]);
    }

    private function generateByPhaseAndSource(EventCarbonFootprintReportData $data, Locale $locale): string
    {
        $categories = array_map(
            fn(Phase $phase) => $this->translator->trans($phase->value, [], 'phase', $locale->value),
            Phase::cases(),
        );

        $series = [];
        foreach (Source::cases() as $source) {
            $totalForSource = (float) $data->emissionsBySource[$source->value]->co2eq();

            if ($totalForSource <= 0.0) {
                continue;
            }

            $rowData = [];
            foreach (Phase::cases() as $phase) {
                $rowData[] = round((float) $data->emissionsByPhaseAndSource[$phase->value][$source->value]->co2eq(), 0);
            }

            $series[] = [
                'name' => $this->translator->trans($source->value, [], 'source', $locale->value),
                'data' => $rowData,
            ];
        }

        return $this->chartClient->generate([
            'chart' => ['type' => 'bar', 'stacked' => true, 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'series' => $series,
            'xaxis' => ['categories' => $categories],
            'colors' => [self::COLOR_TEAL, self::COLOR_LIME, ...self::PALETTE],
            'legend' => ['show' => true],
        ]);
    }

    private function generateByScope(OrganizationalCarbonFootprintReportData $data, Locale $locale): string
    {
        $categories = [];
        $values = [];

        foreach (Scope::cases() as $scope) {
            $categories[] = $this->translator->trans($scope->value, [], 'scope', $locale->value);
            $values[] = round((float) ($data->emissionsByScope[$scope->value] ?? new Emissions())->co2eq(), 0);
        }

        return $this->chartClient->generate([
            'chart' => ['type' => 'bar', 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'series' => [['name' => 'CO2eq', 'data' => $values]],
            'xaxis' => ['categories' => $categories],
            'colors' => [self::COLOR_TEAL],
        ]);
    }

    private function generateByProperty(OrganizationalCarbonFootprintReportData $data, Locale $locale): string
    {
        $categories = [];
        $values = [];

        foreach ($data->emissionsByProperty as $row) {
            $categories[] = null === $row['propertyId'] ? $this->translator->trans('Sin asignar a propiedad', [], 'messages', $locale->value) : $row['name'];
            $values[] = round((float) $row['emissions']->co2eq(), 0);
        }

        return $this->chartClient->generate([
            'chart' => ['type' => 'bar', 'animations' => ['enabled' => false], 'height' => 600, 'width' => 800],
            'plotOptions' => ['bar' => ['horizontal' => true]],
            'series' => [['name' => 'CO2eq', 'data' => $values]],
            'xaxis' => ['categories' => $categories],
            'colors' => [self::COLOR_LIME],
        ]);
    }
}
