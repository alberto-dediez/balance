<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;

final class OrganizationalEmployeeContributionsAggregator
{
    /**
     * @param list<OrganizationalEmployeeContribution> $answered
     * @param array<string, FactorBasis>               $factorBasisByMobilityId
     * @param list<string>                             $blockResolver
     */
    public function aggregate(array $answered, int $totalEmployees, array $factorBasisByMobilityId, array $blockResolver): OrganizationalEmployeeContributionsAggregation
    {
        $count = count($answered);

        if (0 === $count) {
            return new OrganizationalEmployeeContributionsAggregation(commutingKmByBlock: [], businessTravelKmByBlock: [], hotelNightsByBlock: []);
        }

        $commutingKmByBlock = [];
        $businessTravelKmByBlock = [];
        $hotelNightsByBlock = [];

        foreach ($answered as $contribution) {
            $block = $this->blockOf($contribution, $blockResolver);
            $weeksPerYear = $contribution->commutingWeeksPerYear() ?? 0;

            foreach ($contribution->commutes() as $commute) {
                $mobilityId = (string) $commute->mobilityId();
                $factorBasis = $factorBasisByMobilityId[$mobilityId] ?? FactorBasis::PerPassenger;
                $km = $factorBasis->kmCoveringOneOccupant($commute->distanceKm() * 2 * $commute->daysPerWeek() * $weeksPerYear, $commute->vehicleOccupants());

                $commutingKmByBlock[$block][$mobilityId] = ($commutingKmByBlock[$block][$mobilityId] ?? 0.0) + $km;
            }

            foreach ($contribution->trips() as $trip) {
                $mobilityId = (string) $trip->mobilityId();
                $factorBasis = $factorBasisByMobilityId[$mobilityId] ?? FactorBasis::PerPassenger;
                $km = $factorBasis->kmCoveringOneOccupant($trip->distanceKm() * $trip->trips(), $trip->vehicleOccupants());

                $businessTravelKmByBlock[$block][$mobilityId] = ($businessTravelKmByBlock[$block][$mobilityId] ?? 0.0) + $km;

                if ($trip->hotelNights() > 0) {
                    $hotelNightsByBlock[$block] = ($hotelNightsByBlock[$block] ?? 0.0) + $trip->hotelNights() * $trip->trips();
                }
            }
        }

        $extrapolationFactor = $totalEmployees / $count;

        return new OrganizationalEmployeeContributionsAggregation(
            commutingKmByBlock: $this->extrapolateKm($commutingKmByBlock, $extrapolationFactor),
            businessTravelKmByBlock: $this->extrapolateKm($businessTravelKmByBlock, $extrapolationFactor),
            hotelNightsByBlock: array_map(
                static fn(float $nights): float => $nights * $extrapolationFactor,
                $hotelNightsByBlock,
            ),
        );
    }

    /** @param list<string> $blockResolver */
    private function blockOf(OrganizationalEmployeeContribution $contribution, array $blockResolver): string
    {
        $workplacePropertyId = $contribution->workplacePropertyId();

        if (null === $workplacePropertyId || !in_array((string) $workplacePropertyId, $blockResolver, true)) {
            return '';
        }

        return (string) $workplacePropertyId;
    }

    /**
     * @param array<string, array<string, float>> $kmByBlock
     *
     * @return array<string, array<string, float>>
     */
    private function extrapolateKm(array $kmByBlock, float $extrapolationFactor): array
    {
        foreach ($kmByBlock as $block => $kmByMobilityId) {
            $kmByBlock[$block] = array_map(
                static fn(float $km): float => $km * $extrapolationFactor,
                $kmByMobilityId,
            );
        }

        return $kmByBlock;
    }
}
