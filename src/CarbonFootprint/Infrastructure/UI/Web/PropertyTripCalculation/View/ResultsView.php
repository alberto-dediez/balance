<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\View;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsResponse;
use App\CarbonFootprint\Infrastructure\UI\Web\Chart\TripChartBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResultsView
{
    public function __construct(
        private TripChartBuilder $chartBuilder,
        private TranslatorInterface $translator,
    ) {}

    /** @return array<string, mixed> */
    public function build(GetTripCalculationResultsResponse $results, ?string $from, ?string $to): array
    {
        return [
            'property' => $results->property,
            'aggregation' => $results->aggregation,
            'mobilityBreakdown' => $results->mobilityBreakdown,
            'contributions' => $results->contributions,
            'from' => $from,
            'to' => $to,
            'trendChart' => $this->chartBuilder->trend($results->calculationsByDay),
            'emissionsSplitChart' => $this->chartBuilder->emissionsSplit($results->mobilityEmissions, $results->overnightEmissions),
            'mobilityKmChart' => $this->chartBuilder->mobilityKm($results->mobilityBreakdown),
            'mobilityEmissionsChart' => $this->chartBuilder->mobilityEmissions($results->mobilityBreakdown),
            'contributionsChart' => $this->chartBuilder->contributions($results->contributions->aggregation),
            'originsChart' => $this->chartBuilder->counts($results->topOrigins),
            'profileChart' => $this->chartBuilder->counts($this->profile($results)),
        ];
    }

    /** @return array<string, int> */
    private function profile(GetTripCalculationResultsResponse $results): array
    {
        return [
            $this->translator->trans('Con pernoctación') => $results->withOvernightStayCount,
            $this->translator->trans('Solo ida') => $results->aggregation->calculations - $results->roundTripCount,
            $this->translator->trans('Ida y vuelta') => $results->roundTripCount,
        ];
    }
}
