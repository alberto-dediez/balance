<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Trip\OriginFlowsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use PHPUnit\Framework\TestCase;

class OriginFlowsCalculatorTest extends TestCase
{
    private OriginFlowsCalculator $sut;

    protected function setUp(): void
    {
        $this->sut = new OriginFlowsCalculator();
    }

    public function testGroupsTripCalculationsByRoundedOriginAndSumsTravellers(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();

        $flows = $this->sut->forTripCalculations([
            $this->tripCalculation($linkId, 40.41681, -3.70381, 'Madrid', people: 2),
            $this->tripCalculation($linkId, 40.41712, -3.70399, 'Madrid, España', people: 3),
            $this->tripCalculation($linkId, 41.38791, 2.16992, 'Barcelona', people: 1),
        ]);

        $this->assertCount(2, $flows);

        $this->assertSame(40.42, $flows[0]->latitude);
        $this->assertSame(-3.7, $flows[0]->longitude);
        $this->assertSame('Madrid', $flows[0]->label);
        $this->assertSame(2, $flows[0]->responses);
        $this->assertSame(5, $flows[0]->travellers);

        $this->assertSame('Barcelona', $flows[1]->label);
        $this->assertSame(1, $flows[1]->travellers);
    }

    public function testCountsOneTravellerWhenTheTripCalculationDoesNotDeclarePeople(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();

        $flows = $this->sut->forTripCalculations([
            $this->tripCalculation($linkId, 40.41681, -3.70381, 'Madrid'),
        ]);

        $this->assertSame(1, $flows[0]->travellers);
    }

    public function testIgnoresTripCalculationsWithoutOriginCoordinates(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();

        $flows = $this->sut->forTripCalculations([
            TripCalculationMother::fromLinkId($linkId),
            $this->tripCalculation($linkId, 40.41681, -3.70381, 'Madrid'),
        ]);

        $this->assertCount(1, $flows);
        $this->assertSame('Madrid', $flows[0]->label);
    }

    public function testAveragesTheDistanceAndAddsUpTheEmissionsOfEachOrigin(): void
    {
        $linkId = (string) TripCalculationLinkId::generate();

        $flows = $this->sut->forTripCalculations([
            $this->tripCalculation($linkId, 40.41681, -3.70381, 'Madrid', distanceKm: 600.0, co2eq: '120'),
            $this->tripCalculation($linkId, 40.41681, -3.70381, 'Madrid', distanceKm: 400.0, co2eq: '80'),
        ]);

        $this->assertSame(500.0, $flows[0]->km);
        $this->assertSame('200', $flows[0]->emissions->co2eq());
    }

    public function testGroupsAttendeeSurveyResponsesCountingOneTravellerEach(): void
    {
        $attendeeSurveyId = 'attendee-survey-id';

        $flows = $this->sut->forAttendeeSurveyResponses([
            $this->attendeeSurveyResponse($attendeeSurveyId, 40.41681, -3.70381, 'Madrid'),
            $this->attendeeSurveyResponse($attendeeSurveyId, 40.41712, -3.70399, 'Madrid'),
        ]);

        $this->assertCount(1, $flows);
        $this->assertSame(2, $flows[0]->responses);
        $this->assertSame(2, $flows[0]->travellers);
    }

    public function testIgnoresUrbanLegsAndResponsesWithoutOrigin(): void
    {
        $attendeeSurveyId = 'attendee-survey-id';

        $flows = $this->sut->forAttendeeSurveyResponses([
            AttendeeSurveyResponseMother::cityOnly($attendeeSurveyId, (string) MobilityId::generate(), 12.0, 1, 1),
            $this->attendeeSurveyResponse($attendeeSurveyId, 40.41681, -3.70381, 'Madrid', distanceKm: 300.0, cityDistanceKm: 12.0),
        ]);

        $this->assertCount(1, $flows);
        $this->assertSame(300.0, $flows[0]->km);
    }

    private function tripCalculation(
        string $linkId,
        float $latitude,
        float $longitude,
        string $label,
        ?int $people = null,
        float $distanceKm = 300.0,
        string $co2eq = '0',
    ): TripCalculation {
        return TripCalculationMother::from(
            tripCalculationLinkId: $linkId,
            people: $people,
            legs: [TripCalculationMother::leg(1, (string) MobilityId::generate(), $distanceKm)],
            mobilityEmissions: new Emissions(co2eq: $co2eq),
            origin: new TripWaypoint(label: $label, point: new GeoPoint($latitude, $longitude)),
        );
    }

    private function attendeeSurveyResponse(
        string $attendeeSurveyId,
        float $latitude,
        float $longitude,
        string $label,
        float $distanceKm = 300.0,
        ?float $cityDistanceKm = null,
    ): AttendeeSurveyResponse {
        $legs = [AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, (string) MobilityId::generate(), $distanceKm)];

        if (null !== $cityDistanceKm) {
            $legs[] = AttendeeSurveyResponseMother::leg(2, LegKind::City, (string) MobilityId::generate(), $cityDistanceKm);
        }

        return AttendeeSurveyResponseMother::from(
            attendeeSurveyId: $attendeeSurveyId,
            cameFromAnotherCity: true,
            intercityTrips: 1,
            legs: $legs,
            origin: new TripWaypoint(label: $label, point: new GeoPoint($latitude, $longitude)),
        );
    }
}
