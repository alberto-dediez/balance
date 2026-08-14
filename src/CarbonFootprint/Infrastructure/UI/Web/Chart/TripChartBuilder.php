<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Chart;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownRow;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TripChartBuilder
{
    public function __construct(private TranslatorInterface $translator) {}

    /**
     * @param list<MobilityBreakdownRow> $rows
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    public function mobilityKm(array $rows): array
    {
        return $this->chart(
            array_map(static fn(MobilityBreakdownRow $row): string => $row->mobility->name(), $rows),
            array_map(static fn(MobilityBreakdownRow $row): float => round($row->km, 2), $rows),
        );
    }

    /**
     * @param list<MobilityBreakdownRow> $rows
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    public function mobilityEmissions(array $rows): array
    {
        return $this->chart(
            array_map(static fn(MobilityBreakdownRow $row): string => $row->mobility->name(), $rows),
            array_map(static fn(MobilityBreakdownRow $row): float => round((float) $row->emissions->co2eq(), 2), $rows),
        );
    }

    /** @return array{categories: list<string>, values: list<float>} */
    public function emissionsSplit(Emissions $mobility, Emissions $overnight): array
    {
        return $this->chart(
            [$this->translator->trans('Movilidad'), $this->translator->trans('Alojamiento')],
            [round((float) $mobility->co2eq(), 2), round((float) $overnight->co2eq(), 2)],
        );
    }

    /** @return array{categories: list<string>, values: list<float>} */
    public function contributions(ProjectContributionsAggregation $aggregation): array
    {
        return $this->chart(
            [
                $this->translator->trans('Pagadas'),
                $this->translator->trans('Pendientes'),
                $this->translator->trans('Fallidas'),
                $this->translator->trans('Caducadas'),
            ],
            [(float) $aggregation->paid, (float) $aggregation->pending, (float) $aggregation->failed, (float) $aggregation->expired],
        );
    }

    /**
     * @param array<string, int> $countsByDay
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    public function trend(array $countsByDay): array
    {
        $categories = array_map(
            static fn(string $day): string => (new \DateTimeImmutable($day))->format('d/m/Y'),
            array_keys($countsByDay),
        );

        return $this->chart($categories, array_map(static fn(int $count): float => (float) $count, array_values($countsByDay)));
    }

    /**
     * @param array<string, int> $counts
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    public function counts(array $counts): array
    {
        return $this->chart(
            array_keys($counts),
            array_map(static fn(int $count): float => (float) $count, array_values($counts)),
        );
    }

    /**
     * @param array<string, int> $legsByDistanceSource
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    public function distanceSources(array $legsByDistanceSource): array
    {
        $categories = [];
        $values = [];

        foreach ($legsByDistanceSource as $source => $legs) {
            if ($legs <= 0) {
                continue;
            }

            $categories[] = $this->distanceSourceLabel($source);
            $values[] = (float) $legs;
        }

        return $this->chart($categories, $values);
    }

    private function distanceSourceLabel(string $source): string
    {
        return match ($source) {
            DistanceSource::Route->value => $this->translator->trans('Ruta calculada'),
            DistanceSource::GreatCircle->value => $this->translator->trans('Distancia en línea recta'),
            DistanceSource::Manual->value => $this->translator->trans('Introducida manualmente'),
            default => $source,
        };
    }

    /**
     * @param list<string> $categories
     * @param list<float>  $values
     *
     * @return array{categories: list<string>, values: list<float>}
     */
    private function chart(array $categories, array $values): array
    {
        if (0.0 === array_sum($values)) {
            return ['categories' => [], 'values' => []];
        }

        return ['categories' => $categories, 'values' => $values];
    }
}
