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
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;

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
        ?string $id = null,
        ?int $people = null,
    ): TripCalculation {
        return self::from(
            tripCalculationLinkId: $tripCalculationLinkId,
            trips: $trips,
            roundTrip: $roundTrip,
            nights: $nights,
            people: $people,
            legs: [self::leg(1, $mobilityId, $distanceKm, $occupants)],
            mobilityEmissions: $mobilityEmissions,
            overnightEmissions: $overnightEmissions,
            id: $id,
        );
    }

    /** @param list<ResolvedLeg> $legs */
    public static function from(
        string $tripCalculationLinkId,
        int $trips = 1,
        bool $roundTrip = false,
        ?int $nights = null,
        ?int $people = null,
        array $legs = [],
        ?Emissions $mobilityEmissions = null,
        ?Emissions $overnightEmissions = null,
        ?string $id = null,
        ?string $emissionFactorGroupId = null,
        int $calculationYear = 2026,
        ?TripWaypoint $origin = null,
        ?TripWaypoint $destination = null,
    ): TripCalculation {
        $tripCalculation = new TripCalculation(id: new TripCalculationId($id ?? (string) TripCalculationId::generate()));
        $tripCalculation->register(
            tripCalculationLinkId: new TripCalculationLinkId($tripCalculationLinkId),
            origin: $origin ?? new TripWaypoint(),
            destination: $destination,
            roundTrip: $roundTrip,
            trips: $trips,
            nights: $nights,
            people: $people,
            legs: $legs,
            mobilityEmissions: $mobilityEmissions ?? new Emissions(),
            overnightEmissions: $overnightEmissions ?? new Emissions(),
            emissionFactorGroupId: new EmissionFactorGroupId($emissionFactorGroupId ?? (string) EmissionFactorGroupId::generate()),
            calculationYear: $calculationYear,
        );

        return $tripCalculation;
    }

    public static function withDestination(
        string $tripCalculationLinkId,
        TripWaypoint $destination,
        ?TripWaypoint $origin = null,
        int $people = 1,
        float $distanceKm = 300.0,
        ?Emissions $mobilityEmissions = null,
    ): TripCalculation {
        return self::from(
            tripCalculationLinkId: $tripCalculationLinkId,
            nights: 1,
            people: $people,
            legs: [self::leg(1, (string) MobilityId::generate(), $distanceKm)],
            mobilityEmissions: $mobilityEmissions,
            origin: $origin,
            destination: $destination,
        );
    }

    public static function withConsent(string $tripCalculationLinkId, ?ConsentProof $consentProof = null): TripCalculation
    {
        $tripCalculation = self::fromLinkId($tripCalculationLinkId);
        $tripCalculation->grantConsent($consentProof ?? ConsentProofMother::granted());

        return $tripCalculation;
    }

    public static function random(): TripCalculation
    {
        return self::fromLinkId((string) TripCalculationLinkId::generate());
    }

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
