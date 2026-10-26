<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\LegacyImport;

use App\CarbonFootprint\Application\TripCalculation\Import\ImportTripCalculationRequest;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Model\Geo\GeoPoint;

final class LegacyCalculationMapper
{
    private const float CO2EQ_TOLERANCE_KG = 0.001;

    /** @param array<string, MobilityId> $mobilities */
    public function request(TripCalculationLinkId $tripCalculationLinkId, LegacyCalculation $calculation, array $mobilities): ImportTripCalculationRequest
    {
        $emissions = new Emissions(co2eq: (string) ($calculation->emissionsTotalGrams / 1000));
        $withOrigin = null !== $calculation->origin;

        return new ImportTripCalculationRequest(
            tripCalculationLinkId: $tripCalculationLinkId,
            origin: $this->waypoint($calculation->origin),
            destination: $this->waypoint($calculation->destination),
            roundTrip: $calculation->roundTrip,
            nights: $calculation->nights,
            people: $calculation->persons,
            mobilityId: $mobilities[$calculation->transport] ?? null,
            vehicleOccupants: 'car' === $calculation->transport ? $calculation->persons : null,
            distanceKm: $withOrigin ? $calculation->distanceMeters / 1000 : null,
            mobilityEmissions: $withOrigin ? $emissions : new Emissions(),
            overnightEmissions: $withOrigin ? new Emissions() : $emissions,
            registeredAt: $calculation->createdAt,
        );
    }

    /** @param list<TripCalculation> $existing */
    public function isDuplicated(LegacyCalculation $calculation, array $existing): bool
    {
        $registeredAt = $calculation->createdAt->format('Y-m-d H:i:s');
        $co2eq = $calculation->emissionsTotalGrams / 1000;

        foreach ($existing as $tripCalculation) {
            if ($tripCalculation->registeredAt()->format('Y-m-d H:i:s') === $registeredAt
                && abs((float) $tripCalculation->emissions()->co2eq() - $co2eq) < self::CO2EQ_TOLERANCE_KG) {
                return true;
            }
        }

        return false;
    }

    private function waypoint(?LegacyPlace $place): ?TripWaypoint
    {
        if (null === $place) {
            return null;
        }

        return new TripWaypoint($place->placeId, $place->formattedAddress, new GeoPoint($place->latitude, $place->longitude));
    }
}
