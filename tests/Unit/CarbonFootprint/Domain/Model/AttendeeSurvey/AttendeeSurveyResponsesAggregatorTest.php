<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyResponsesAggregator;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use PHPUnit\Framework\TestCase;

class AttendeeSurveyResponsesAggregatorTest extends TestCase
{
    private AttendeeSurveyResponsesAggregator $sut;

    protected function setUp(): void
    {
        $this->sut = new AttendeeSurveyResponsesAggregator(new TripLegsAccumulator());
    }

    public function testReturnsEmptyAggregationWhenNoResponses(): void
    {
        $aggregation = $this->sut->aggregate([], 100);

        $this->assertSame([], $aggregation->kmByMobilityId);
        $this->assertSame(0.0, $aggregation->overnightStays);
    }

    public function testCalculatesKmForIntercityBlockOnlyWhenCameFromAnotherCity(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            mobilityId: $mobilityId,
            distanceKm: 300.0,
            trips: 2,
            occupants: 3,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(600.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testDoublesTheDistanceOfARoundTrip(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            mobilityId: $mobilityId,
            distanceKm: 300.0,
            trips: 1,
            occupants: 1,
            roundTrip: true,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(600.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testOccupantsAreClampedToMinimumOfOne(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 0,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1, factorBasisByMobilityId: [$mobilityId => FactorBasis::PerVehicle]);

        $this->assertEqualsWithDelta(100.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testCalculatesKmForCityBlockOnlyWhenTheAttendeeMovedWithinTheCity(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::cityOnly(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            mobilityId: $mobilityId,
            distanceKm: 10.0,
            trips: 3,
            occupants: 2,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(30.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testIgnoresTheCityLegsWhenTheAttendeeDidNotMoveWithinTheCity(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            movedWithinCity: false,
            cityTrips: 3,
            legs: [AttendeeSurveyResponseMother::leg(1, LegKind::City, $mobilityId, 10.0)],
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertSame([], $aggregation->kmByMobilityId);
    }

    public function testSplitsAMultimodalTripBetweenItsMeansOfTransport(): void
    {
        $flightMobilityId = (string) MobilityId::generate();
        $trainMobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::multimodalIntercity(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            legs: [
                ['mobilityId' => $flightMobilityId, 'distanceKm' => 400.0, 'occupants' => 1],
                ['mobilityId' => $trainMobilityId, 'distanceKm' => 70.0, 'occupants' => 1],
            ],
            roundTrip: true,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(800.0, $aggregation->kmByMobilityId[$flightMobilityId], 0.0001);
        $this->assertEqualsWithDelta(140.0, $aggregation->kmByMobilityId[$trainMobilityId], 0.0001);
    }

    public function testMixesIntercityAndCityBlocksAndSumsPerMobilityId(): void
    {
        $sharedMobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            cameFromAnotherCity: true,
            intercityTrips: 2,
            movedWithinCity: true,
            cityTrips: 1,
            legs: [
                AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, $sharedMobilityId, 100.0),
                AttendeeSurveyResponseMother::leg(1, LegKind::City, $sharedMobilityId, 20.0),
            ],
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(220.0, $aggregation->kmByMobilityId[$sharedMobilityId], 0.0001);
    }

    public function testSumsOvernightStaysOnlyWhenUsedHotel(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $withHotel = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            usedHotel: true,
            hotelNights: 2,
            hotelPeople: 3,
        );
        $withoutHotel = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            usedHotel: false,
        );

        $aggregation = $this->sut->aggregate([$withHotel, $withoutHotel], realAttendees: 2);

        $this->assertEqualsWithDelta(2.0, $aggregation->overnightStays, 0.0001);
    }

    public function testIgnoresTheVehicleOccupantsWhenTheFactorIsPerPassenger(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $shared = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: $attendeeSurveyId,
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 3,
        );

        $alone = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: $attendeeSurveyId,
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 1,
        );

        $sharedAggregation = $this->sut->aggregate([$shared], realAttendees: 30);
        $aloneAggregation = $this->sut->aggregate([$alone], realAttendees: 30);

        $this->assertEqualsWithDelta(3000.0, $sharedAggregation->kmByMobilityId[$mobilityId], 0.0001);
        $this->assertEqualsWithDelta(
            $aloneAggregation->kmByMobilityId[$mobilityId],
            $sharedAggregation->kmByMobilityId[$mobilityId],
            0.0001,
        );
    }

    public function testExtrapolatesEachLegOfAMultimodalTripByTheSameFactorWhateverItsOccupants(): void
    {
        $taxiMobilityId = (string) MobilityId::generate();
        $busMobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::multimodalIntercity(
            attendeeSurveyId: (string) AttendeeSurveyId::generate(),
            legs: [
                ['mobilityId' => $taxiMobilityId, 'distanceKm' => 10.0, 'occupants' => 1],
                ['mobilityId' => $busMobilityId, 'distanceKm' => 100.0, 'occupants' => 30],
            ],
        );

        $aggregation = $this->sut->aggregate(
            [$response],
            realAttendees: 300,
            factorBasisByMobilityId: [$taxiMobilityId => FactorBasis::PerVehicle, $busMobilityId => FactorBasis::PerPassenger],
        );

        $this->assertEqualsWithDelta(3000.0, $aggregation->kmByMobilityId[$taxiMobilityId], 0.0001);
        $this->assertEqualsWithDelta(30000.0, $aggregation->kmByMobilityId[$busMobilityId], 0.0001);
    }

    public function testSplitsTheVehicleDistanceBetweenItsOccupantsWhenTheFactorIsPerVehicle(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            $attendeeSurveyId,
            $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 4,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 4, factorBasisByMobilityId: [$mobilityId => FactorBasis::PerVehicle]);

        $this->assertEqualsWithDelta(100.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testExtrapolatesMobilityByTheNumberOfResponses(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            $attendeeSurveyId,
            $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 1,
            usedHotel: true,
            hotelNights: 1,
            hotelPeople: 4,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 40);

        $this->assertEqualsWithDelta(4000.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testExtrapolatesTheOvernightStaysOfOnePersonByTheNumberOfResponses(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            $attendeeSurveyId,
            $mobilityId,
            distanceKm: 100.0,
            trips: 1,
            occupants: 4,
            usedHotel: true,
            hotelNights: 1,
            hotelPeople: 4,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 40);

        $this->assertEqualsWithDelta(40.0, $aggregation->overnightStays, 0.0001);
    }

    public function testExtrapolatesByRealAttendeesDividedByTheNumberOfResponses(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $responses = [
            AttendeeSurveyResponseMother::intercityOnly($attendeeSurveyId, $mobilityId, 100.0, 1, 1, usedHotel: true, hotelNights: 1),
            AttendeeSurveyResponseMother::intercityOnly($attendeeSurveyId, $mobilityId, 100.0, 1, 1, usedHotel: true, hotelNights: 1),
        ];

        $aggregation = $this->sut->aggregate($responses, realAttendees: 40);

        $this->assertEqualsWithDelta(4000.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
        $this->assertEqualsWithDelta(40.0, $aggregation->overnightStays, 0.0001);
    }
}
