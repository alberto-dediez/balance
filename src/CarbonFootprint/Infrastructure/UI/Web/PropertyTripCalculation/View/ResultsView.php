<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\PropertyTripCalculation\View;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsResponse;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Infrastructure\UI\Web\Chart\TripChartBuilder;
use App\CarbonFootprint\Infrastructure\UI\Web\Map\TripFlowsMapBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ResultsView
{
    public function __construct(
        private TripChartBuilder $chartBuilder,
        private TripFlowsMapBuilder $mapBuilder,
        private TranslatorInterface $translator,
    ) {}

    /** @return array<string, mixed> */
    public function build(GetTripCalculationResultsResponse $results, ?string $from, ?string $to): array
    {
        $showTransport = $this->showTransport($results);
        $showOvernightStay = $this->showOvernightStay($results);

        return [
            'ownerName' => $results->ownerName,
            'property' => $results->property,
            'aggregation' => $results->aggregation,
            'mobilityBreakdown' => $results->mobilityBreakdown,
            'contributions' => $results->contributions,
            'emissionsPerPerson' => $results->emissionsPerPerson,
            'from' => $from,
            'to' => $to,
            'trendChart' => $this->chartBuilder->trend($results->calculationsByDay),
            'emissionsSplitChart' => $this->chartBuilder->emissionsSplit($results->mobilityEmissions, $results->overnightEmissions),
            'mobilityKmChart' => $this->chartBuilder->mobilityKm($results->mobilityBreakdown),
            'mobilityEmissionsChart' => $this->chartBuilder->mobilityEmissions($results->mobilityBreakdown),
            'contributionsChart' => $this->chartBuilder->contributions($results->contributions->aggregation),
            'originsChart' => $this->chartBuilder->counts($results->topOrigins),
            'destinationsChart' => $this->chartBuilder->counts($results->topDestinations),
            'profileChart' => $this->chartBuilder->counts($this->profile($results, $showTransport, $showOvernightStay)),
            'unlocatedResponses' => $results->unlocatedCalculations,
            'showTransport' => $showTransport,
            'showOvernightStay' => $showOvernightStay,
        ] + $this->map($results);
    }

    /** @return array<string, mixed> */
    private function map(GetTripCalculationResultsResponse $results): array
    {
        if (null === $results->property) {
            return [
                'mapFlows' => $this->mapBuilder->originDestinationFlows($results->originDestinationFlows),
                'mapDestination' => null,
                'mapArrows' => true,
                'undestinedResponses' => $results->undestinedCalculations,
            ];
        }

        return [
            'mapFlows' => $this->mapBuilder->flows($results->originFlows),
            'mapDestination' => $this->mapBuilder->destination($results->destination, $results->property->name()),
            'mapArrows' => false,
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
            || $results->aggregation->overnightStays > 0
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
