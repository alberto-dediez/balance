<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyResponsesAggregator;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use PHPUnit\Framework\TestCase;

class AttendeeSurveyResponsesAggregatorTest extends TestCase
{
    private AttendeeSurveyResponsesAggregator $sut;

    protected function setUp(): void
    {
        $this->sut = new AttendeeSurveyResponsesAggregator();
    }

    public function testReturnsEmptyAggregationWhenNoResponses(): void
    {
        $aggregation = $this->sut->aggregate([], 100);

        $this->assertSame([], $aggregation->kmByMobilityId);
        $this->assertSame(0.0, $aggregation->hotelNights);
    }

    public function testCalculatesKmForIntercityBlockOnlyWhenCameFromAnotherCity(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: $attendeeSurveyId,
            mobilityId: $mobilityId,
            distanceKm: 300.0,
            days: 2,
            occupants: 3,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(200.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testOccupantsAreClampedToMinimumOfOne(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::intercityOnly(
            attendeeSurveyId: $attendeeSurveyId,
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            days: 1,
            occupants: 0,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(100.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testCalculatesKmForCityBlockRegardlessOfCameFromAnotherCity(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::cityOnly(
            attendeeSurveyId: $attendeeSurveyId,
            mobilityId: $mobilityId,
            distanceKm: 10.0,
            days: 3,
            occupants: 2,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(15.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
    }

    public function testMixesIntercityAndCityBlocksAndSumsPerMobilityId(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $sharedMobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityDays: 2,
            intercityMobilityId: $sharedMobilityId,
            intercityVehicleOccupants: 1,
            intercityDistanceKm: 100.0,
            cityDays: 1,
            cityMobilityId: $sharedMobilityId,
            cityVehicleOccupants: 1,
            cityDistanceKm: 20.0,
        );

        $aggregation = $this->sut->aggregate([$response], realAttendees: 1);

        $this->assertEqualsWithDelta(220.0, $aggregation->kmByMobilityId[$sharedMobilityId], 0.0001);
    }

    public function testSumsHotelNightsOnlyWhenUsedHotel(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();

        $withHotel = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            usedHotel: true,
            hotelNights: 2,
        );
        $withoutHotel = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            usedHotel: false,
        );

        $aggregation = $this->sut->aggregate([$withHotel, $withoutHotel], realAttendees: 2);

        $this->assertEqualsWithDelta(2.0, $aggregation->hotelNights, 0.0001);
    }

    public function testExtrapolatesByRealAttendeesDividedByResponsesCount(): void
    {
        $attendeeSurveyId = (string) AttendeeSurveyId::generate();
        $mobilityId = (string) MobilityId::generate();

        $responses = [
            AttendeeSurveyResponseMother::intercityOnly($attendeeSurveyId, $mobilityId, 100.0, 1, 1, usedHotel: true, hotelNights: 1),
            AttendeeSurveyResponseMother::intercityOnly($attendeeSurveyId, $mobilityId, 100.0, 1, 1, usedHotel: true, hotelNights: 1),
        ];

        $aggregation = $this->sut->aggregate($responses, realAttendees: 40);

        $this->assertEqualsWithDelta(4000.0, $aggregation->kmByMobilityId[$mobilityId], 0.0001);
        $this->assertEqualsWithDelta(40.0, $aggregation->hotelNights, 0.0001);
    }
}
