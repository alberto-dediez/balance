<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseRequest;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseService;
use App\CarbonFootprint\Application\Trip\LegInput;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class RegisterAttendeeSurveyResponseServiceTest extends TestCase
{
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;

    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;

    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryEventRepository $eventRepository;

    private InMemoryPlaceFinder $placeFinder;

    private RegisterAttendeeSurveyResponseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->placeFinder = new InMemoryPlaceFinder();

        $this->sut = new RegisterAttendeeSurveyResponseService(
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            $this->mobilityRepository,
            $this->eventRepository,
            new EventVenueLocator(new InMemoryPropertyRepository()),
            $this->placeFinder,
            new TripLegsResolver(new GreatCircleRouteDistanceCalculator()),
        );
    }

    public function testRegistersAResponseWithBothBlocks(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);
        $this->placeFinder->add('hotel', 'Hotel Madrid', 40.4200, -3.7000);

        $response = ($this->sut)(new RegisterAttendeeSurveyResponseRequest(
            token: $attendeeSurvey->token(),
            cameFromAnotherCity: true,
            originPlaceReference: 'sevilla',
            usedHotel: true,
            accommodationPlaceReference: 'hotel',
            hotelNights: 2,
            intercityRoundTrip: true,
            intercityTrips: 5,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), vehicleOccupants: 2)],
            movedWithinCity: true,
            cityOriginPlaceReference: null,
            cityRoundTrip: true,
            cityTrips: 3,
            cityLegs: [new LegInput(mobilityId: (string) $mobility->id(), vehicleOccupants: 1)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertTrue($stored->cameFromAnotherCity());
        $this->assertTrue($stored->usedHotel());
        $this->assertSame(2, $stored->hotelNights());
        $this->assertTrue($stored->intercityRoundTrip());
        $this->assertTrue($stored->movedWithinCity());
        $this->assertSame(3, $stored->cityTrips());
        $this->assertCount(1, $stored->legsOfKind(LegKind::Intercity));
        $this->assertCount(1, $stored->legsOfKind(LegKind::City));
    }

    public function testForcesASingleIntercityTripWhenTheAttendeeStayedOvernight(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);
        $this->placeFinder->add('hotel', 'Hotel Madrid', 40.4200, -3.7000);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: 'sevilla',
            usedHotel: true,
            accommodationPlaceReference: 'hotel',
            hotelNights: 3,
            intercityTrips: 9,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id())],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertSame(1, $stored->intercityTrips());
    }

    public function testUsesTheAccommodationAsTheDefaultCityOrigin(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Metro', TravelMode::Transit);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);
        $this->placeFinder->add('hotel', 'Hotel Madrid', 40.4200, -3.7000);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: 'sevilla',
            usedHotel: true,
            accommodationPlaceReference: 'hotel',
            hotelNights: 1,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id())],
            movedWithinCity: true,
            cityTrips: 2,
            cityLegs: [new LegInput(mobilityId: (string) $mobility->id())],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertSame('hotel', $stored->cityOriginPlaceReference());
        $this->assertSame(40.4200, $stored->cityOriginLatitude());
    }

    public function testCalculatesTheLegDistanceFromTheResolvedPlaces(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Avión', TravelMode::Flight);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: 'sevilla',
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id())],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);

        $leg = $stored->legsOfKind(LegKind::Intercity)[0];

        $this->assertSame(DistanceSource::GreatCircle, $leg->distanceSource());
        $this->assertEqualsWithDelta(431.0, (float) $leg->distanceKm(), 15.0);
    }

    public function testFallsBackToTheManualDistanceWhenTheMobilityHasNoTravelMode(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Otro medio', null);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: 'sevilla',
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 250.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);

        $leg = $stored->legsOfKind(LegKind::Intercity)[0];

        $this->assertSame(DistanceSource::Manual, $leg->distanceSource());
        $this->assertSame(250.0, $leg->distanceKm());
    }

    public function testSplitsAMultimodalTripIntoOneLegPerMeansOfTransport(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $flight = $this->mobility('Avión', TravelMode::Flight);
        $train = $this->mobility('Tren', TravelMode::Transit);

        $this->placeFinder->add('sevilla', 'Sevilla, España', 37.3891, -5.9845);
        $this->placeFinder->add('madrid', 'Madrid, España', 40.4168, -3.7038);
        $this->placeFinder->add('hotel', 'Hotel Toledo', 39.8628, -4.0273);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: 'sevilla',
            usedHotel: true,
            accommodationPlaceReference: 'hotel',
            hotelNights: 1,
            intercityLegs: [
                new LegInput(mobilityId: (string) $flight->id(), destinationPlaceReference: 'madrid'),
                new LegInput(mobilityId: (string) $train->id()),
            ],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);

        $legs = $stored->legsOfKind(LegKind::Intercity);

        $this->assertCount(2, $legs);
        $this->assertTrue($legs[0]->mobilityId()->equals($flight->id()));
        $this->assertSame('Madrid, España', $legs[0]->destinationLabel());
        $this->assertTrue($legs[1]->mobilityId()->equals($train->id()));
        $this->assertSame('Hotel Toledo', $legs[1]->destinationLabel());
        $this->assertGreaterThan((float) $legs[1]->distanceKm(), (float) $legs[0]->distanceKm());
    }

    public function testIgnoresTheCoordinatesTheClientDidNotResolveThroughThePlaceFinder(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            originPlaceReference: '40.0,-3.0',
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 120.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertNull($stored->originLatitude());
        $this->assertSame(DistanceSource::Manual, $stored->legsOfKind(LegKind::Intercity)[0]->distanceSource());
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->expectException(AttendeeSurveyTokenInvalidException::class);

        ($this->sut)($this->request(token: 'invalid-token'));
    }

    public function testThrowsWhenSurveyIsClosed(): void
    {
        $attendeeSurvey = AttendeeSurveyMother::registeredAttendeeSurvey();
        $attendeeSurvey->close(10, ConfidenceLevel::Ninety, 0.1);
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $this->expectException(AttendeeSurveyClosedException::class);

        ($this->sut)($this->request(token: $attendeeSurvey->token()));
    }

    public function testThrowsWhenBothBlocksAreEmpty(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(token: $attendeeSurvey->token(), intercityLegs: []));
    }

    public function testThrowsWhenAMobilityOfALegDoesNotExistInCatalog(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) MobilityId::generate(), manualDistanceKm: 10.0)],
        ));
    }

    public function testThrowsWhenALegHasNoDistanceAtAll(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Otro medio', null);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id())],
        ));
    }

    public function testThrowsWhenHotelUsedWithoutNights(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            usedHotel: true,
            hotelNights: null,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 10.0)],
        ));
    }

    public function testThrowsWhenTheNumberOfTripsIsMissing(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityTrips: null,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 10.0)],
        ));
    }

    private function surveyWithVenue(): AttendeeSurvey
    {
        $event = EventMother::from(name: 'Event', id: (string) EventId::generate());
        $event->setVenueLocation('venue', 'IFEMA, Madrid', 40.4680, -3.6160);
        $this->eventRepository->add($event);

        $attendeeSurvey = AttendeeSurveyMother::fromEventId((string) $event->id());

        $this->attendeeSurveyRepository->add($attendeeSurvey);

        return $attendeeSurvey;
    }

    private function mobility(string $name, ?TravelMode $travelMode): Mobility
    {
        $mobility = null !== $travelMode
            ? MobilityMother::fromNameIdAndTravelMode($name, (string) MobilityId::generate(), $travelMode)
            : MobilityMother::fromName($name);

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    /**
     * @param list<LegInput> $intercityLegs
     * @param list<LegInput> $cityLegs
     */
    private function request(
        string $token,
        bool $cameFromAnotherCity = true,
        ?string $originPlaceReference = null,
        bool $usedHotel = false,
        ?string $accommodationPlaceReference = null,
        ?int $hotelNights = null,
        bool $intercityRoundTrip = false,
        ?int $intercityTrips = 1,
        array $intercityLegs = [],
        bool $movedWithinCity = false,
        ?string $cityOriginPlaceReference = null,
        bool $cityRoundTrip = false,
        ?int $cityTrips = null,
        array $cityLegs = [],
    ): RegisterAttendeeSurveyResponseRequest {
        return new RegisterAttendeeSurveyResponseRequest(
            token: $token,
            cameFromAnotherCity: $cameFromAnotherCity,
            originPlaceReference: $originPlaceReference,
            usedHotel: $usedHotel,
            accommodationPlaceReference: $accommodationPlaceReference,
            hotelNights: $hotelNights,
            intercityRoundTrip: $intercityRoundTrip,
            intercityTrips: $intercityTrips,
            intercityLegs: $intercityLegs,
            movedWithinCity: $movedWithinCity,
            cityOriginPlaceReference: $cityOriginPlaceReference,
            cityRoundTrip: $cityRoundTrip,
            cityTrips: $cityTrips,
            cityLegs: $cityLegs,
        );
    }
}
