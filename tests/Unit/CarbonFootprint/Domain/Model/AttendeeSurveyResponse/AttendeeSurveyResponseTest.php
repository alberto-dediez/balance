<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse;

use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRegistered;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class AttendeeSurveyResponseTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testKeepsTheLegsOrderedByBlockAndPosition(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: 'AS1',
            cameFromAnotherCity: true,
            intercityTrips: 1,
            movedWithinCity: true,
            cityTrips: 1,
            legs: [
                AttendeeSurveyResponseMother::leg(1, LegKind::City, $mobilityId, 5.0),
                AttendeeSurveyResponseMother::leg(2, LegKind::Intercity, $mobilityId, 70.0),
                AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, $mobilityId, 400.0),
            ],
        );

        $legs = $response->legs();

        $this->assertCount(3, $legs);
        $this->assertSame(400.0, $legs[0]->distanceKm());
        $this->assertSame(70.0, $legs[1]->distanceKm());
        $this->assertSame(5.0, $legs[2]->distanceKm());
    }

    public function testFiltersTheLegsOfEachBlock(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::from(
            attendeeSurveyId: 'AS1',
            cameFromAnotherCity: true,
            intercityTrips: 1,
            movedWithinCity: true,
            cityTrips: 1,
            legs: [
                AttendeeSurveyResponseMother::leg(1, LegKind::Intercity, $mobilityId, 400.0),
                AttendeeSurveyResponseMother::leg(2, LegKind::Intercity, $mobilityId, 70.0),
                AttendeeSurveyResponseMother::leg(1, LegKind::City, $mobilityId, 5.0),
            ],
        );

        $this->assertCount(2, $response->legsOfKind(LegKind::Intercity));
        $this->assertCount(1, $response->legsOfKind(LegKind::City));
    }

    public function testTheRegisteredEventSurvivesASerializationRoundTrip(): void
    {
        $mobilityId = (string) MobilityId::generate();

        $response = AttendeeSurveyResponseMother::multimodalIntercity(
            attendeeSurveyId: 'AS1',
            legs: [
                ['mobilityId' => $mobilityId, 'distanceKm' => 400.0, 'occupants' => 2],
                ['mobilityId' => $mobilityId, 'distanceKm' => 70.0, 'occupants' => 1],
            ],
            trips: 3,
            roundTrip: true,
        );

        /** @var AttendeeSurveyResponseRegistered $event */
        $event = array_values($response->recordedEvents())[0];

        $rebuilt = AttendeeSurveyResponseRegistered::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertTrue($rebuilt->intercityRoundTrip());
        $this->assertSame(3, $rebuilt->intercityTrips());
        $this->assertCount(2, $rebuilt->legs());
        $this->assertSame(400.0, $rebuilt->legs()[0]->distanceKm);
        $this->assertSame(2, $rebuilt->legs()[0]->vehicleOccupants);
        $this->assertSame(LegKind::Intercity, $rebuilt->legs()[1]->kind);
    }
}
