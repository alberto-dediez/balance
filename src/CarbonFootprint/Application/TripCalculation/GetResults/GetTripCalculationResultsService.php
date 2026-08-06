<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

final class GetTripCalculationResultsService
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private PropertyAuthorizer $propertyAuthorizer,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationRepository $tripCalculationRepository,
        private TripCalculationsAggregator $aggregator,
        private MobilityRepository $mobilityRepository,
        private ActivityEmissionsResolver $activityEmissionsResolver,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
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
            return new GetTripCalculationResultsResponse(
                property: $property,
                tripCalculationLink: null,
                aggregation: $this->aggregator->aggregate([]),
                mobilityBreakdown: [],
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

        return new GetTripCalculationResultsResponse(
            property: $property,
            tripCalculationLink: $tripCalculationLink,
            aggregation: $aggregation,
            mobilityBreakdown: $this->mobilityBreakdown($aggregation->kmByMobilityId, $tripCalculations),
        );
    }

    /**
     * @param array<string, float>  $kmByMobilityId
     * @param list<TripCalculation> $tripCalculations
     *
     * @return list<MobilityBreakdownRow>
     */
    private function mobilityBreakdown(array $kmByMobilityId, array $tripCalculations): array
    {
        [$emissionFactorGroupId, $year] = $this->factorContext($tripCalculations);

        $rows = [];

        foreach ($kmByMobilityId as $mobilityId => $km) {
            $mobility = $this->mobilityRepository->find(new MobilityId($mobilityId));

            if (null === $mobility) {
                continue;
            }

            $rows[] = new MobilityBreakdownRow(
                mobility: $mobility,
                km: $km,
                emissions: $this->emissionsFor($mobilityId, $km, $emissionFactorGroupId, $year),
            );
        }

        usort($rows, static fn(MobilityBreakdownRow $a, MobilityBreakdownRow $b): int => $b->km <=> $a->km);

        return $rows;
    }

    /**
     * The factors the answers were calculated with are the ones that make their kilometres
     * comparable, so the most recent answer of the period leads.
     *
     * @param list<TripCalculation> $tripCalculations
     *
     * @return array{0: EmissionFactorGroupId|null, 1: int}
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

        return [
            $this->emissionFactorGroupRepository->findDefault()?->id(),
            (int) (new \DateTimeImmutable('NOW'))->format('Y'),
        ];
    }

    private function emissionsFor(string $mobilityId, float $km, ?EmissionFactorGroupId $emissionFactorGroupId, int $year): Emissions
    {
        if (null === $emissionFactorGroupId || $km <= 0.0) {
            return new Emissions();
        }

        try {
            return $this->activityEmissionsResolver->resolveForGroupAndYear(
                $emissionFactorGroupId,
                $year,
                ActivityType::Mobility,
                $mobilityId,
                null,
                new Quantity($km, Unit::Kilometer),
                null,
            )->emissions;
        } catch (\InvalidArgumentException) {
            return new Emissions();
        }
    }
}
