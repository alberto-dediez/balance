<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\View;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsResponse;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
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
        $showTransport = $this->showTransport($results);
        $showOvernightStay = $this->showOvernightStay($results);

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
            'profileChart' => $this->chartBuilder->counts($this->profile($results, $showTransport, $showOvernightStay)),
            'showTransport' => $showTransport,
            'showOvernightStay' => $showOvernightStay,
        ];
    }

    private function showTransport(GetTripCalculationResultsResponse $results): bool
    {
        return $this->scope($results)->includesTransport()
            || [] !== $results->mobilityBreakdown
            || (float) $results->mobilityEmissions->co2eq() > 0;
    }

    private function showOvernightStay(GetTripCalculationResultsResponse $results): bool
    {
        return $this->scope($results)->includesOvernightStay()
            || $results->aggregation->nights > 0
            || $results->withOvernightStayCount > 0;
    }

    private function scope(GetTripCalculationResultsResponse $results): TripCalculationScope
    {
        return $results->tripCalculationLink?->scope() ?? TripCalculationScope::Both;
    }

    /** @return array<string, int> */
    private function profile(GetTripCalculationResultsResponse $results, bool $showTransport, bool $showOvernightStay): array
    {
        $profile = [];

        if ($showOvernightStay) {
            $profile[$this->translator->trans('Con pernoctación')] = $results->withOvernightStayCount;
        }

        if ($showTransport) {
            $profile[$this->translator->trans('Solo ida')] = $results->aggregation->calculations - $results->roundTripCount;
            $profile[$this->translator->trans('Ida y vuelta')] = $results->roundTripCount;
        }

        return $profile;
    }
}
