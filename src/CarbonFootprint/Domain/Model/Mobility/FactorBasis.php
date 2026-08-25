<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Mobility;

enum FactorBasis: string
{
    case PerPassenger = 'per_passenger';
    case PerVehicle   = 'per_vehicle';

    public function kmCoveringAllOccupants(float $distanceKm, int $occupants): float
    {
        return match ($this) {
            self::PerPassenger => $distanceKm * max(1, $occupants),
            self::PerVehicle   => $distanceKm,
        };
    }

    public function kmCoveringOneOccupant(float $distanceKm, int $occupants): float
    {
        return match ($this) {
            self::PerPassenger => $distanceKm,
            self::PerVehicle   => $distanceKm / max(1, $occupants),
        };
    }
}
