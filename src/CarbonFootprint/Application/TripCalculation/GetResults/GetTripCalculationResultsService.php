<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionFilter;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownCalculator;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

final class GetTripCalculationResultsService
{
    private const int TOP_ORIGINS = 8;

    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyAuthorizer $propertyAuthorizer,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationRepository $tripCalculationRepository,
        private TripCalculationsAggregator $aggregator,
        private MobilityBreakdownCalculator $mobilityBreakdownCalculator,
        private ProjectContributionRepository $projectContributionRepository,
        private ProjectContributionsAggregator $contributionsAggregator,
    ) {}

    public function __invoke(GetTripCalculationResultsRequest $request, User $user): GetTripCalculationResultsResponse
    {
        $property = $this->propertyRepository->find($request->propertyId);

        if (null === $property) {
            throw new \InvalidArgumentException('Property not found');
        }

        $this->propertyAuthorizer->denyUnlessCanDoThat($property, Action::View, $user);

        $tripCalculationLink = $this->tripCalculationLinkRepository->findByPropertyId($request->propertyId);

        if (null === $tripCalculationLink) {
            $aggregation = $this->aggregator->aggregate([]);

            return new GetTripCalculationResultsResponse(
                property: $property,
                tripCalculationLink: null,
                aggregation: $aggregation,
                mobilityBreakdown: [],
                contributions: $this->contributions([], $aggregation),
                mobilityEmissions: new Emissions(),
                overnightEmissions: new Emissions(),
                calculationsByDay: [],
                topOrigins: [],
                withOvernightStayCount: 0,
                roundTripCount: 0,
            );
        }

        $tripCalculations = $this->tripCalculationRepository->findByFilter(
            new TripCalculationFilter(
                tripCalculationLinkId: $tripCalculationLink->id(),
                from: $request->from,
                to: $request->to,
                limit: 0,
            ),
        );

        $aggregation = $this->aggregator->aggregate($tripCalculations);
        [$emissionFactorGroupId, $calculationYear] = $this->factorContext($tripCalculations);

        return new GetTripCalculationResultsResponse(
            property: $property,
            tripCalculationLink: $tripCalculationLink,
            aggregation: $aggregation,
            mobilityBreakdown: $this->mobilityBreakdownCalculator->calculate($aggregation->kmByMobilityId, $emissionFactorGroupId, $calculationYear),
            contributions: $this->contributions($tripCalculations, $aggregation),
            mobilityEmissions: $this->sumEmissions($tripCalculations, static fn(TripCalculation $tripCalculation): Emissions => $tripCalculation->mobilityEmissions()),
            overnightEmissions: $this->sumEmissions($tripCalculations, static fn(TripCalculation $tripCalculation): Emissions => $tripCalculation->overnightEmissions()),
            calculationsByDay: $this->calculationsByDay($tripCalculations),
            topOrigins: $this->topOrigins($tripCalculations),
            withOvernightStayCount: count(array_filter($tripCalculations, static fn(TripCalculation $tripCalculation): bool => ($tripCalculation->nights() ?? 0) > 0)),
            roundTripCount: count(array_filter($tripCalculations, static fn(TripCalculation $tripCalculation): bool => $tripCalculation->roundTrip())),
        );
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
     * @param list<TripCalculation> $tripCalculations
     *
     * @return array<string, int>
     */
    private function topOrigins(array $tripCalculations): array
    {
        $byOrigin = [];

        foreach ($tripCalculations as $tripCalculation) {
            $origin = $tripCalculation->originLabel();

            if (null === $origin || '' === trim($origin)) {
                continue;
            }

            $byOrigin[$origin] = ($byOrigin[$origin] ?? 0) + 1;
        }

        arsort($byOrigin);

        return array_slice($byOrigin, 0, self::TOP_ORIGINS, true);
    }

    /** @param list<TripCalculation> $tripCalculations */
    private function contributions(array $tripCalculations, TripCalculationsAggregation $aggregation): ContributionsSummary
    {
        $originIds = array_map(static fn(TripCalculation $tripCalculation): string => (string) $tripCalculation->id(), $tripCalculations);

        $contributions = [] === $originIds
            ? $this->contributionsAggregator->aggregate([])
            : $this->contributionsAggregator->aggregate($this->projectContributionRepository->findByFilter(
                new ProjectContributionFilter(
                    origin: ProjectContributionOrigin::PropertyTripCalculation,
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
