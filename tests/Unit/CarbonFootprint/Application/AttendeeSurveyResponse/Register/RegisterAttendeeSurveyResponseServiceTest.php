<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurveyResponse\Register;

use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseRequest;
use App\CarbonFootprint\Application\AttendeeSurveyResponse\Register\RegisterAttendeeSurveyResponseService;
use App\CarbonFootprint\Application\Trip\LegInput;
use App\CarbonFootprint\Application\Trip\TripEmissionsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosedException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseConsentGranted;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Geo\DistanceSource;
use App\CarbonFootprint\Domain\Model\Geo\GreatCircleRouteDistanceCalculator;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\LegKind;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsResolver;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Domain\Model\Consent\ConsentProofMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class RegisterAttendeeSurveyResponseServiceTest extends TestCase
{
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;

    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;

    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryEventRepository $eventRepository;

    private InMemoryOvernightStayRepository $overnightStayRepository;

    private InMemoryEmissionFactorGroupRepository $emissionFactorGroupRepository;

    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;

    private InMemoryPlaceFinder $placeFinder;

    private EventId $eventId;

    private EmissionFactorGroupId $emissionFactorGroupId;

    private int $calculationYear;

    private SpyDomainEventSubscriber $spySubscriber;

    private RegisterAttendeeSurveyResponseService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->emissionFactorGroupRepository = new InMemoryEmissionFactorGroupRepository();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->placeFinder = new InMemoryPlaceFinder();
        $this->spySubscriber = new SpyDomainEventSubscriber();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $this->emissionFactorGroupRepository->add($emissionFactorGroup);

        $this->emissionFactorGroupId = $emissionFactorGroup->id();
        $this->calculationYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        $this->sut = $this->service($this->emissionFactorGroupRepository);
    }

    private function service(InMemoryEmissionFactorGroupRepository $emissionFactorGroupRepository): RegisterAttendeeSurveyResponseService
    {
        $activityEmissionsResolver = new ActivityEmissionsResolver(
            new EmissionFactorSelectorRegistry([
                new MobilityEmissionFactorSelector($this->mobilityRepository),
                new OvernightStayEmissionFactorSelector($this->overnightStayRepository),
            ]),
            new EmissionsCalculator(),
        );

        return new RegisterAttendeeSurveyResponseService(
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            $this->mobilityRepository,
            $this->eventRepository,
            new EventVenueLocator(new InMemoryPropertyRepository()),
            $this->placeFinder,
            new TripLegsResolver(new GreatCircleRouteDistanceCalculator()),
            new TripLegsAccumulator(),
            new TripEmissionsCalculator($activityEmissionsResolver, $this->overnightStayRepository),
            $emissionFactorGroupRepository,
            $this->eventCarbonFootprintRepository,
            ConsentProofMother::POLICY_VERSION,
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

    public function testStoresTheIndividualEmissionsOfTheAttendee(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');
        $this->overnightStayWithFactor('4.0');

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            usedHotel: true,
            hotelNights: 2,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertEqualsWithDelta(20.0, (float) $stored->mobilityEmissions()->co2eq(), 0.001);
        $this->assertEqualsWithDelta(8.0, (float) $stored->overnightEmissions()->co2eq(), 0.001);
        $this->assertEqualsWithDelta(28.0, (float) $stored->emissions()->co2eq(), 0.001);
        $this->assertEqualsWithDelta(28.0, (float) $response->emissions->co2eq(), 0.001);
        $this->assertEquals($this->emissionFactorGroupId, $stored->emissionFactorGroupId());
        $this->assertSame($this->calculationYear, $stored->calculationYear());
    }

    public function testAddsTheEmissionsOfBothBlocksApplyingTripsRoundTripAndOccupants(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $intercityMobility = $this->mobilityWithFactor('Coche', '0.2');
        $cityMobility = $this->mobilityWithFactor('Metro', '0.1', TravelMode::Transit);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityRoundTrip: true,
            intercityTrips: 2,
            intercityLegs: [new LegInput(mobilityId: (string) $intercityMobility->id(), vehicleOccupants: 2, manualDistanceKm: 100.0)],
            movedWithinCity: true,
            cityRoundTrip: false,
            cityTrips: 3,
            cityLegs: [new LegInput(mobilityId: (string) $cityMobility->id(), manualDistanceKm: 10.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertEqualsWithDelta(43.0, (float) $stored->mobilityEmissions()->co2eq(), 0.001);
    }

    public function testUsesTheEmissionFactorsOfTheEventCarbonFootprint(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();

        $eventCarbonFootprintGroup = EmissionFactorGroupMother::fromName('Grupo del evento');
        $this->emissionFactorGroupRepository->add($eventCarbonFootprintGroup);
        $this->eventCarbonFootprintRepository->add(EventCarbonFootprintMother::from(
            eventId: (string) $this->eventId,
            id: (string) EventCarbonFootprintId::generate(),
            emissionFactorGroupId: $eventCarbonFootprintGroup->id(),
        ));

        $mobility = MobilityMother::fromNameIdAndTravelMode('Coche', (string) MobilityId::generate(), TravelMode::Driving);
        $mobility->setEmissionFactor(new EmissionFactor('0.5', '0.5', '0', '0', 2026, $eventCarbonFootprintGroup->id(), Unit::Kilometer));
        $mobility->setEmissionFactor(new EmissionFactor('0.2', '0.2', '0', '0', $this->calculationYear, $this->emissionFactorGroupId, Unit::Kilometer));
        $this->mobilityRepository->add($mobility);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertEqualsWithDelta(50.0, (float) $stored->mobilityEmissions()->co2eq(), 0.001);
        $this->assertEquals($eventCarbonFootprintGroup->id(), $stored->emissionFactorGroupId());
        $this->assertSame(2026, $stored->calculationYear());
    }

    public function testRegistersTheResponseWithoutEmissionsWhenThereIsNoEmissionFactorGroup(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $response = ($this->service(new InMemoryEmissionFactorGroupRepository()))($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertSame(0.0, (float) $stored->emissions()->co2eq());
        $this->assertNull($stored->emissionFactorGroupId());
        $this->assertNull($stored->calculationYear());
    }

    public function testRegistersTheResponseWithoutEmissionsWhenAMobilityHasNoEmissionFactor(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $response = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertSame(0.0, (float) $stored->emissions()->co2eq());
        $this->assertNull($stored->emissionFactorGroupId());
    }

    public function testDoesNotExtrapolateTheEmissionsToTheAttendeesOfTheEvent(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobilityWithFactor('Coche', '0.2');

        $first = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $second = ($this->sut)($this->request(
            token: $attendeeSurvey->token(),
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
        ));

        $this->assertEqualsWithDelta(20.0, (float) $first->emissions->co2eq(), 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $second->emissions->co2eq(), 0.001);
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

        $this->eventId = $event->id();

        $attendeeSurvey = AttendeeSurveyMother::fromEventId((string) $event->id());

        $this->attendeeSurveyRepository->add($attendeeSurvey);

        return $attendeeSurvey;
    }

    private function mobilityWithFactor(string $name, string $co2eq, TravelMode $travelMode = TravelMode::Driving): Mobility
    {
        $mobility = MobilityMother::fromNameIdAndTravelMode($name, (string) MobilityId::generate(), $travelMode);

        $mobility->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Kilometer,
        ));

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    private function overnightStayWithFactor(string $co2eq): void
    {
        $overnightStay = OvernightStayMother::registeredOvernightStay();

        $overnightStay->setEmissionFactor(new EmissionFactor(
            $co2eq,
            $co2eq,
            '0',
            '0',
            $this->calculationYear,
            $this->emissionFactorGroupId,
            Unit::Pernoctacion,
        ));

        $this->overnightStayRepository->add($overnightStay);
    }

    private function mobility(string $name, ?TravelMode $travelMode): Mobility
    {
        $mobility = null !== $travelMode
            ? MobilityMother::fromNameIdAndTravelMode($name, (string) MobilityId::generate(), $travelMode)
            : MobilityMother::fromName($name);

        $this->mobilityRepository->add($mobility);

        return $mobility;
    }

    public function testStoresTheConsentProofOfTheAttendee(): void
    {
        $attendeeSurvey = $this->surveyWithVenue();
        $mobility = $this->mobility('Coche', TravelMode::Driving);

        $response = ($this->sut)(new RegisterAttendeeSurveyResponseRequest(
            token: $attendeeSurvey->token(),
            cameFromAnotherCity: true,
            originPlaceReference: null,
            usedHotel: false,
            accommodationPlaceReference: null,
            hotelNights: null,
            intercityRoundTrip: false,
            intercityTrips: 1,
            intercityLegs: [new LegInput(mobilityId: (string) $mobility->id(), manualDistanceKm: 100.0)],
            movedWithinCity: false,
            cityOriginPlaceReference: null,
            cityRoundTrip: false,
            cityTrips: null,
            cityLegs: [],
            consentIp: '203.0.113.9',
            consentUserAgent: 'Mozilla/5.0',
            consentAcceptLanguage: 'es-ES',
        ));

        $stored = $this->attendeeSurveyResponseRepository->find($response->attendeeSurveyResponseId);

        $this->assertNotNull($stored);
        $this->assertTrue($stored->hasConsent());

        $consentProof = $stored->consentProof();

        $this->assertNotNull($consentProof);
        $this->assertSame('203.0.113.9', $consentProof->ip());
        $this->assertSame(ConsentProofMother::POLICY_VERSION, $consentProof->policyVersion());
        $this->assertSame(LocaleGetter::locale()->value, $consentProof->locale());
        $this->assertNotEmpty(array_filter($this->spySubscriber->events(), static fn($e) => $e instanceof AttendeeSurveyResponseConsentGranted));
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
