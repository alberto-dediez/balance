<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownCalculator;
use App\CarbonFootprint\Application\Trip\OriginFlowsCalculator;
use App\CarbonFootprint\Application\Trip\PerPersonTripEmissionsCalculator;
use App\CarbonFootprint\Application\Trip\TripCalculationContributionOrigin;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class TripCalculationResultsBuilder
{
    private const int TOP_LABELS = 8;

    public function __construct(
        private TripCalculationRepository $tripCalculationRepository,
        private TripCalculationsAggregator $aggregator,
        private MobilityBreakdownCalculator $mobilityBreakdownCalculator,
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectContributionsAggregator $contributionsAggregator,
        private MobilityFactorBasisProvider $mobilityFactorBasisProvider,
        private PerPersonTripEmissionsCalculator $perPersonTripEmissionsCalculator,
        private OriginFlowsCalculator $originFlowsCalculator,
    ) {}

    public function build(
        TripCalculationLink $tripCalculationLink,
        ?Property $property,
        string $ownerName,
        ?GeoPoint $destination,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): GetTripCalculationResultsResponse {
        $tripCalculations = $this->tripCalculationRepository->findByFilter(
            new TripCalculationFilter(
                tripCalculationLinkId: $tripCalculationLink->id(),
                from: $from,
                to: $to,
                limit: 0,
            ),
        );

        $aggregation = $this->aggregator->aggregate($tripCalculations, $this->mobilityFactorBasisProvider->factorBasisByMobilityId());
        [$emissionFactorGroupId, $calculationYear] = $this->factorContext($tripCalculations);
        $isForAccount = !$tripCalculationLink->isForProperty();

        return new GetTripCalculationResultsResponse(
            property: $property,
            ownerName: $ownerName,
            tripCalculationLink: $tripCalculationLink,
            aggregation: $aggregation,
            mobilityBreakdown: $this->mobilityBreakdownCalculator->calculate($aggregation->kmByMobilityId, $emissionFactorGroupId, $calculationYear),
            contributions: $this->contributions($tripCalculations, $aggregation, TripCalculationContributionOrigin::for($tripCalculationLink)),
            mobilityEmissions: $this->sumEmissions($tripCalculations, static fn(TripCalculation $tripCalculation): Emissions => $tripCalculation->mobilityEmissions()),
            overnightEmissions: $this->sumEmissions($tripCalculations, static fn(TripCalculation $tripCalculation): Emissions => $tripCalculation->overnightEmissions()),
            emissionsPerPerson: $this->perPersonTripEmissionsCalculator->forTripCalculations($tripCalculations),
            calculationsByDay: $this->calculationsByDay($tripCalculations),
            topOrigins: $this->topLabels($tripCalculations, static fn(TripCalculation $tripCalculation): ?string => $tripCalculation->originLabel()),
            withOvernightStayCount: count(array_filter($tripCalculations, static fn(TripCalculation $tripCalculation): bool => ($tripCalculation->nights() ?? 0) > 0)),
            roundTripCount: count(array_filter($tripCalculations, static fn(TripCalculation $tripCalculation): bool => $tripCalculation->roundTrip())),
            originFlows: $this->originFlowsCalculator->forTripCalculations($tripCalculations),
            destination: $destination,
            unlocatedCalculations: $this->unlocatedCalculations($tripCalculations),
            topDestinations: $this->topLabels($tripCalculations, static fn(TripCalculation $tripCalculation): ?string => $tripCalculation->destinationLabel()),
            originDestinationFlows: $isForAccount ? $this->originFlowsCalculator->forTripCalculationsByDestination($tripCalculations) : [],
            undestinedCalculations: $isForAccount ? $this->undestinedCalculations($tripCalculations) : 0,
        );
    }

    public function empty(?Property $property, string $ownerName, ?GeoPoint $destination): GetTripCalculationResultsResponse
    {
        $aggregation = $this->aggregator->aggregate([]);

        return new GetTripCalculationResultsResponse(
            property: $property,
            ownerName: $ownerName,
            tripCalculationLink: null,
            aggregation: $aggregation,
            mobilityBreakdown: [],
            contributions: $this->contributions([], $aggregation, ProjectContributionOrigin::PropertyTripCalculation),
            mobilityEmissions: new Emissions(),
            overnightEmissions: new Emissions(),
            emissionsPerPerson: new Emissions(),
            calculationsByDay: [],
            topOrigins: [],
            withOvernightStayCount: 0,
            roundTripCount: 0,
            destination: $destination,
        );
    }

    /** @param list<TripCalculation> $tripCalculations */
    private function undestinedCalculations(array $tripCalculations): int
    {
        return count(array_filter(
            $tripCalculations,
            static fn(TripCalculation $tripCalculation): bool => null === $tripCalculation->destinationLatitude() || null === $tripCalculation->destinationLongitude(),
        ));
    }

    /** @param list<TripCalculation> $tripCalculations */
    private function unlocatedCalculations(array $tripCalculations): int
    {
        return count(array_filter(
            $tripCalculations,
            static fn(TripCalculation $tripCalculation): bool => null === $tripCalculation->originLatitude() || null === $tripCalculation->originLongitude(),
        ));
    }

    /**
     * @param list<TripCalculation>                $tripCalculations
     * @param callable(TripCalculation): Emissions $emissions
     */
    private function sumEmissions(array $tripCalculations, callable $emissions): Emissions
    {
        $total = new Emissions();

        foreach ($tripCalculations as $tripCalculation) {
            $total = $total->add($emissions($tripCalculation));
        }

        return $total;
    }

    /**
     * @param list<TripCalculation> $tripCalculations
     *
     * @return array<string, int>
     */
    private function calculationsByDay(array $tripCalculations): array
    {
        $byDay = [];

        foreach ($tripCalculations as $tripCalculation) {
            $day = $tripCalculation->registeredAt()->format('Y-m-d');
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        }

        ksort($byDay);

        return $byDay;
    }

    /**
     * @param list<TripCalculation>                    $tripCalculations
     * @param callable(TripCalculation): (string|null) $label
     *
     * @return array<string, int>
     */
    private function topLabels(array $tripCalculations, callable $label): array
    {
        $byLabel = [];

        foreach ($tripCalculations as $tripCalculation) {
            $value = $label($tripCalculation);

            if (null === $value || '' === trim($value)) {
                continue;
            }

            $byLabel[$value] = ($byLabel[$value] ?? 0) + 1;
        }

        arsort($byLabel);

        return array_slice($byLabel, 0, self::TOP_LABELS, true);
    }

    /** @param list<TripCalculation> $tripCalculations */
    private function contributions(array $tripCalculations, TripCalculationsAggregation $aggregation, ProjectContributionOrigin $origin): ContributionsSummary
    {
        $originIds = array_map(static fn(TripCalculation $tripCalculation): string => (string) $tripCalculation->id(), $tripCalculations);

        $contributions = [] === $originIds
            ? $this->contributionsAggregator->aggregate([])
            : $this->contributionsAggregator->aggregate($this->projectContributionRepository->findByFilter(
                new ProjectContributionFilter(
                    origin: $origin,
                    originIds: $originIds,
                    limit: 0,
                ),
            ));

        return new ContributionsSummary(
            aggregation: $contributions,
            startedRate: $this->rate($contributions->contributions, $aggregation->calculations),
            paidRate: $this->rate($contributions->paid, $aggregation->calculations),
            completionRate: $this->rate($contributions->paid, $contributions->contributions),
            compensatedEmissionsRate: $this->compensatedEmissionsRate($contributions, $aggregation),
        );
    }

    private function compensatedEmissionsRate(ProjectContributionsAggregation $contributions, TripCalculationsAggregation $aggregation): float
    {
        $emitted = (float) $aggregation->totalEmissions->co2eq();

        if ($emitted <= 0.0) {
            return 0.0;
        }

        return $contributions->paidTonnes * 1000 * 100 / $emitted;
    }

    private function rate(int $part, int $total): float
    {
        return 0 === $total ? 0.0 : $part * 100 / $total;
    }

    /**
     * @param list<TripCalculation> $tripCalculations
     *
     * @return array{0: EmissionFactorGroupId|null, 1: int|null}
     */
    private function factorContext(array $tripCalculations): array
    {
        foreach ($tripCalculations as $tripCalculation) {
            $emissionFactorGroupId = $tripCalculation->emissionFactorGroupId();
            $calculationYear = $tripCalculation->calculationYear();

            if (null !== $emissionFactorGroupId && null !== $calculationYear) {
                return [$emissionFactorGroupId, $calculationYear];
            }
        }

        return [null, null];
    }
}
