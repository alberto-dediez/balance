<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Trip;

final class TripLegsAccumulator
{
    /**
     * @param array<string, float> $kmByMobilityId
     * @param list<TripLeg>        $legs
     */
    public function accumulate(array &$kmByMobilityId, array $legs, ?int $trips, bool $roundTrip): void
    {
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
            $occupants = max(1, $leg->vehicleOccupants() ?? 1);
            $km = $distanceKm * $trips * $roundTripFactor / $occupants;

            $kmByMobilityId[$mobilityId] = ($kmByMobilityId[$mobilityId] ?? 0.0) + $km;
        }
    }
}
