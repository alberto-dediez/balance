<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;

class TripCalculationMother
{
    public static function fromLinkId(string $tripCalculationLinkId): TripCalculation
    {
        return self::singleLeg($tripCalculationLinkId, (string) MobilityId::generate(), 300.0);
    }

    public static function singleLeg(
        string $tripCalculationLinkId,
        string $mobilityId,
        float $distanceKm,
        int $trips = 1,
        int $occupants = 1,
        bool $roundTrip = false,
        ?int $nights = null,
        ?Emissions $mobilityEmissions = null,
        ?Emissions $overnightEmissions = null,
    ): TripCalculation {
        return self::from(
            tripCalculationLinkId: $tripCalculationLinkId,
            trips: $trips,
            roundTrip: $roundTrip,
            nights: $nights,
            legs: [self::leg(1, $mobilityId, $distanceKm, $occupants)],
            mobilityEmissions: $mobilityEmissions,
            overnightEmissions: $overnightEmissions,
        );
    }

    /** @param list<ResolvedLeg> $legs */
    public static function from(
        string $tripCalculationLinkId,
        int $trips = 1,
        bool $roundTrip = false,
        ?int $nights = null,
        array $legs = [],
        ?Emissions $mobilityEmissions = null,
        ?Emissions $overnightEmissions = null,
        ?string $id = null,
        ?string $emissionFactorGroupId = null,
        int $calculationYear = 2026,
    ): TripCalculation {
        $tripCalculation = new TripCalculation(id: new TripCalculationId($id ?? (string) TripCalculationId::generate()));
        $tripCalculation->register(
            tripCalculationLinkId: new TripCalculationLinkId($tripCalculationLinkId),
            origin: new TripWaypoint(),
            roundTrip: $roundTrip,
            trips: $trips,
            nights: $nights,
            legs: $legs,
            mobilityEmissions: $mobilityEmissions ?? new Emissions(),
            overnightEmissions: $overnightEmissions ?? new Emissions(),
            emissionFactorGroupId: new EmissionFactorGroupId($emissionFactorGroupId ?? (string) EmissionFactorGroupId::generate()),
            calculationYear: $calculationYear,
        );

        return $tripCalculation;
    }

    public static function random(): TripCalculation
    {
        return self::fromLinkId((string) TripCalculationLinkId::generate());
    }

    /** Reconstituted as the repository would hand it back, so its date can be placed in the past. */
    public static function registeredAt(string $tripCalculationLinkId, string $registeredAt): TripCalculation
    {
        return new TripCalculation(
            id: TripCalculationId::generate(),
            tripCalculationLinkId: new TripCalculationLinkId($tripCalculationLinkId),
            trips: 1,
            registeredAt: new \DateTimeImmutable($registeredAt),
        );
    }

    public static function leg(
        int $position,
        string $mobilityId,
        float $distanceKm,
        int $occupants = 1,
        DistanceSource $distanceSource = DistanceSource::Manual,
    ): ResolvedLeg {
        return new ResolvedLeg(
            position: $position,
            kind: LegKind::Intercity,
            mobilityId: new MobilityId($mobilityId),
            vehicleOccupants: $occupants,
            origin: new TripWaypoint(),
            destination: new TripWaypoint(),
            distanceKm: $distanceKm,
            distanceSource: $distanceSource,
        );
    }
}
