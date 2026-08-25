<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\ResolvedLeg;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use PHPUnit\Framework\TestCase;

class TripLegsAccumulatorTest extends TestCase
{
    private TripLegsAccumulator $sut;

    protected function setUp(): void
    {
        $this->sut = new TripLegsAccumulator();
    }

    public function testAccumulatesTheDistanceOfASingleLeg(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0)], 1, false);

        $this->assertEqualsWithDelta(100.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testMultipliesByTheNumberOfTrips(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0)], 3, false);

        $this->assertEqualsWithDelta(300.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testDoublesARoundTrip(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0)], 1, true);

        $this->assertEqualsWithDelta(200.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testMultipliesTheDistanceByTheOccupantsWhenTheFactorIsPerPassenger(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate(
            $kmByMobilityId,
            [$this->leg($mobilityId, 100.0, 4)],
            1,
            false,
            [$mobilityId => FactorBasis::PerPassenger],
        );

        $this->assertEqualsWithDelta(400.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testCountsTheVehicleOnceWhenTheFactorIsPerVehicle(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate(
            $kmByMobilityId,
            [$this->leg($mobilityId, 100.0, 4)],
            1,
            false,
            [$mobilityId => FactorBasis::PerVehicle],
        );

        $this->assertEqualsWithDelta(100.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testFallsBackToPerPassengerForAnUnknownMobility(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0, 4)], 1, false);

        $this->assertEqualsWithDelta(400.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testAccumulatesPerPersonTheFullDistanceWhenTheFactorIsPerPassenger(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulatePerPerson(
            $kmByMobilityId,
            [$this->leg($mobilityId, 100.0, 4)],
            1,
            false,
            [$mobilityId => FactorBasis::PerPassenger],
        );

        $this->assertEqualsWithDelta(100.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testAccumulatesPerPersonTheVehicleDistanceSplitBetweenItsOccupants(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulatePerPerson(
            $kmByMobilityId,
            [$this->leg($mobilityId, 100.0, 4)],
            1,
            false,
            [$mobilityId => FactorBasis::PerVehicle],
        );

        $this->assertEqualsWithDelta(25.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testAccumulatesPerPersonEachLegByItsOwnOccupants(): void
    {
        $carMobilityId = (string) MobilityId::generate();
        $trainMobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulatePerPerson(
            $kmByMobilityId,
            [$this->leg($carMobilityId, 300.0, 3), $this->leg($trainMobilityId, 200.0, 3)],
            1,
            false,
            [$carMobilityId => FactorBasis::PerVehicle, $trainMobilityId => FactorBasis::PerPassenger],
        );

        $this->assertEqualsWithDelta(100.0, $kmByMobilityId[$carMobilityId], 0.001);
        $this->assertEqualsWithDelta(200.0, $kmByMobilityId[$trainMobilityId], 0.001);
    }

    public function testAddsUpLegsOfTheSameMeansOfTransport(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate(
            $kmByMobilityId,
            [$this->leg($mobilityId, 100.0), $this->leg($mobilityId, 50.0)],
            1,
            false,
        );

        $this->assertEqualsWithDelta(150.0, $kmByMobilityId[$mobilityId], 0.001);
    }

    public function testIgnoresLegsWithoutDistance(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, null)], 1, false);

        $this->assertSame([], $kmByMobilityId);
    }

    public function testIgnoresBlocksWithoutTrips(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0)], 0, false);
        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0)], null, false);

        $this->assertSame([], $kmByMobilityId);
    }

    private function leg(string $mobilityId, ?float $distanceKm, int $occupants = 1): ResolvedLeg
    {
        return new ResolvedLeg(
            position: 1,
            kind: LegKind::Intercity,
            mobilityId: new MobilityId($mobilityId),
            vehicleOccupants: $occupants,
            origin: new TripWaypoint(),
            destination: new TripWaypoint(),
            distanceKm: $distanceKm,
            distanceSource: DistanceSource::Manual,
        );
    }
}
