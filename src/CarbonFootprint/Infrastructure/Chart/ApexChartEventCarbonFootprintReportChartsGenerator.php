<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Chart;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Chart\ApexChartClient;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ApexChartEventCarbonFootprintReportChartsGenerator implements EventCarbonFootprintReportChartsGenerator
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

    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportCharts
    {
        return new EventCarbonFootprintReportCharts(
            bySource: $this->generateBySource($data, $locale),
            byPhase: $this->generateByPhase($data, $locale),
            byPhaseAndSource: $this->generateByPhaseAndSource($data, $locale),
            compensation: $this->generateCompensation($data, $locale),
        );
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

    private function generateBySource(EventCarbonFootprintReportData $data, Locale $locale): string
    {
        $labels = [];
        $values = [];
        $colors = [];

        $paletteIndex = 0;
        foreach (Source::cases() as $source) {
            $co2eq = (float) $data->emissionsBySource[$source->value]->co2eq();

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
}
