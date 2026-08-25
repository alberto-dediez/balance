<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;

final class TripLegsAccumulator
{
    /**
     * @param array<string, float>       $kmByMobilityId
     * @param list<TripLeg>              $legs
     * @param array<string, FactorBasis> $factorBasisByMobilityId
     */
    public function accumulate(
        array &$kmByMobilityId,
        array $legs,
        ?int $trips,
        bool $roundTrip,
        array $factorBasisByMobilityId = [],
    ): void {
        $this->accumulateLegs($kmByMobilityId, $legs, $trips, $roundTrip, $factorBasisByMobilityId, false);
    }

    /**
     * @param array<string, float>       $kmByMobilityId
     * @param list<TripLeg>              $legs
     * @param array<string, FactorBasis> $factorBasisByMobilityId
     */
    public function accumulatePerPerson(
        array &$kmByMobilityId,
        array $legs,
        ?int $trips,
        bool $roundTrip,
        array $factorBasisByMobilityId = [],
    ): void {
        $this->accumulateLegs($kmByMobilityId, $legs, $trips, $roundTrip, $factorBasisByMobilityId, true);
    }

    /**
     * @param array<string, float>       $kmByMobilityId
     * @param list<TripLeg>              $legs
     * @param array<string, FactorBasis> $factorBasisByMobilityId
     */
    private function accumulateLegs(
        array &$kmByMobilityId,
        array $legs,
        ?int $trips,
        bool $roundTrip,
        array $factorBasisByMobilityId,
        bool $perPerson,
    ): void {
        if (null === $trips || $trips < 1) {
            return;
        }

        $roundTripFactor = $roundTrip ? 2 : 1;

        foreach ($legs as $leg) {
            $distanceKm = $leg->distanceKm();

            if (null === $distanceKm) {
                continue;
            }

            $mobilityId = (string) $leg->mobilityId();
            $factorBasis = $factorBasisByMobilityId[$mobilityId] ?? FactorBasis::PerPassenger;
            $occupants = $leg->vehicleOccupants() ?? 1;
            $distanceKm *= $trips * $roundTripFactor;

            $km = $perPerson
                ? $factorBasis->kmCoveringOneOccupant($distanceKm, $occupants)
                : $factorBasis->kmCoveringAllOccupants($distanceKm, $occupants);

            $kmByMobilityId[$mobilityId] = ($kmByMobilityId[$mobilityId] ?? 0.0) + $km;
        }
    }
}
