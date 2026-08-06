<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Trip;

use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
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

    public function testSplitsTheDistanceBetweenTheOccupantsOfTheVehicle(): void
    {
        $mobilityId = (string) MobilityId::generate();
        $kmByMobilityId = [];

        $this->sut->accumulate($kmByMobilityId, [$this->leg($mobilityId, 100.0, 4)], 1, false);

        $this->assertEqualsWithDelta(25.0, $kmByMobilityId[$mobilityId], 0.001);
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
