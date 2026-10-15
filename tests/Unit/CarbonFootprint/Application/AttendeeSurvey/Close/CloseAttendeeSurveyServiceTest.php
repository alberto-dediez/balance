<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\Close;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\MobilityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\OvernightStayAnswerHandler;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Application\AttendeeSurvey\Close\CloseAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Close\CloseAttendeeSurveyService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyResponsesAggregator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CloseAttendeeSurveyServiceTest extends TestCase
{
    private const YEAR = 2026;

    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;
    private InMemoryOvernightStayRepository $overnightStayRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private InMemoryActivityRepository $activityRepository;
    private Mobility $mobility;
    private CloseAttendeeSurveyService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->mobility = MobilityMother::registeredMobility();
        $this->mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear(self::YEAR));
        $this->mobilityRepository = MobilityRepositoryMother::inMemory();
        $this->mobilityRepository->add($this->mobility);

        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $mobilitySelector = new MobilityEmissionFactorSelector($this->mobilityRepository);
        $overnightStaySelector = new OvernightStayEmissionFactorSelector($this->overnightStayRepository);

        $handlers = new ActivityAnswerHandlerRegistry([
            new MobilityAnswerHandler(
                new RegisterMobilityActivityService($this->activityRepository, $authorizer, $mobilitySelector, $calculator),
                new UpdateMobilityActivityService($this->activityRepository, $authorizer, $mobilitySelector, $calculator),
            ),
            new OvernightStayAnswerHandler(
                new RegisterOvernightStayActivityService($this->activityRepository, $authorizer, $overnightStaySelector, $calculator),
                new UpdateOvernightStayActivityService($this->activityRepository, $authorizer, $overnightStaySelector, $calculator),
            ),
        ]);

        $this->sut = new CloseAttendeeSurveyService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            new AttendeeSurveyResponsesAggregator(new TripLegsAccumulator()),
            $this->overnightStayRepository,
            new StageAnswersSynchronizer($handlers, $this->countryResolver()),
            new MobilityFactorBasisProvider($this->mobilityRepository),
        );
    }

    public function testClosingWithoutResponsesKeepsManualActivitiesAndTransitionsStatus(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventId = (string) $eventCarbonFootprint->eventId();
        $attendeeSurvey = $this->registeredAttendeeSurvey($eventId);

        $manualActivityId = $this->registerActivity();
        $eventCarbonFootprint->addActivity($manualActivityId, new ActivityPlacement(Phase::Celebration, Source::AttendeesMobility));

        ($this->sut)(
            new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 100, ConfidenceLevel::Ninety),
            $this->ownerUser(),
        );

        $activityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesMobility)->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($manualActivityId));
        $this->assertTrue($eventCarbonFootprint->status()->isAttendeesAnswered());

        $closedSurvey = $this->attendeeSurveyRepository->find($attendeeSurvey->id());
        $this->assertNotNull($closedSurvey);
        $this->assertTrue($closedSurvey->status()->isClosed());
        $this->assertSame(100, $closedSurvey->realAttendees());
    }

    public function testClosingWithResponsesReplacesManualActivitiesWithGeneratedOnes(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventId = (string) $eventCarbonFootprint->eventId();
        $attendeeSurvey = $this->registeredAttendeeSurvey($eventId);

        $manualActivityId = $this->registerActivity();
        $eventCarbonFootprint->addActivity($manualActivityId, new ActivityPlacement(Phase::Celebration, Source::AttendeesMobility));

        $overnightStay = OvernightStayMother::registeredOvernightStay();
        $overnightStay->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion)->withYear(self::YEAR));
        $this->overnightStayRepository->add($overnightStay);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::intercityOnly((string) $attendeeSurvey->id(), (string) $this->mobility->id(), 300.0, 2, 1, usedHotel: true, hotelNights: 2),
        );

        $response = ($this->sut)(
            new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 10, ConfidenceLevel::Ninety),
            $this->ownerUser(),
        );

        $this->assertFalse($response->overnightStayCatalogMissing);

        $mobilityActivityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesMobility)->activityIds();
        $this->assertCount(1, $mobilityActivityIds);
        $this->assertFalse($mobilityActivityIds[0]->equals($manualActivityId));

        $overnightStayActivityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesAccommodation)->activityIds();
        $this->assertCount(1, $overnightStayActivityIds);

        $this->assertTrue($eventCarbonFootprint->status()->isAttendeesAnswered());
    }

    public function testClosingWithHotelNightsButNoCatalogItemWarnsAndSkips(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventId = (string) $eventCarbonFootprint->eventId();
        $attendeeSurvey = $this->registeredAttendeeSurvey($eventId);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::intercityOnly((string) $attendeeSurvey->id(), (string) $this->mobility->id(), 300.0, 2, 1, usedHotel: true, hotelNights: 2),
        );

        $response = ($this->sut)(
            new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 10, ConfidenceLevel::Ninety),
            $this->ownerUser(),
        );

        $this->assertTrue($response->overnightStayCatalogMissing);

        $overnightStayActivityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesAccommodation)->activityIds();
        $this->assertCount(0, $overnightStayActivityIds);
    }

    public function testMarginOfErrorIsPersistedOnTheSurvey(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventId = (string) $eventCarbonFootprint->eventId();
        $attendeeSurvey = $this->registeredAttendeeSurvey($eventId);

        for ($i = 0; $i < 5; ++$i) {
            $this->attendeeSurveyResponseRepository->add(AttendeeSurveyResponseMother::intercityOnly((string) $attendeeSurvey->id(), (string) $this->mobility->id(), 100.0, 1, 1));
        }

        ($this->sut)(
            new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 50, ConfidenceLevel::Ninety),
            $this->ownerUser(),
        );

        $closedSurvey = $this->attendeeSurveyRepository->find($attendeeSurvey->id());
        $this->assertNotNull($closedSurvey);
        $this->assertGreaterThan(0.0, $closedSurvey->marginOfError());
    }

    public function testReClosingRegeneratesActivities(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $eventId = (string) $eventCarbonFootprint->eventId();
        $attendeeSurvey = $this->registeredAttendeeSurvey($eventId);

        $this->attendeeSurveyResponseRepository->add(
            AttendeeSurveyResponseMother::intercityOnly((string) $attendeeSurvey->id(), (string) $this->mobility->id(), 100.0, 1, 1),
        );

        ($this->sut)(new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 10, ConfidenceLevel::Ninety), $this->ownerUser());

        $firstActivityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesMobility)->activityIds();
        $this->assertCount(1, $firstActivityIds);

        ($this->sut)(new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 20, ConfidenceLevel::NinetyFive), $this->ownerUser());

        $secondActivityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Celebration)->filterBySource(Source::AttendeesMobility)->activityIds();
        $this->assertCount(1, $secondActivityIds);
        $this->assertFalse($firstActivityIds[0]->equals($secondActivityIds[0]));

        $closedSurvey = $this->attendeeSurveyRepository->find($attendeeSurvey->id());
        $this->assertNotNull($closedSurvey);
        $this->assertSame(20, $closedSurvey->realAttendees());
        $this->assertSame(ConfidenceLevel::NinetyFive, $closedSurvey->confidenceLevel());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredAttendeeSurvey((string) $eventCarbonFootprint->eventId());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), 10, ConfidenceLevel::Ninety),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function registeredAttendeeSurvey(string $eventId): AttendeeSurvey
    {
        $attendeeSurvey = AttendeeSurveyMother::fromEventId($eventId);
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        return $attendeeSurvey;
    }

    private function registerActivity(): ActivityId
    {
        $response = (new RegisterMobilityActivityService(
            $this->activityRepository,
            new ActivityAuthorizer(),
            new MobilityEmissionFactorSelector($this->mobilityRepository),
            EmissionsCalculatorMother::create(),
        ))(
            new RegisterMobilityActivityRequest(
                accountId: new AccountId('account-id'),
                mobilityId: $this->mobility->id(),
                quantity: ActivityMother::quantity(Unit::Kilometer, 50.0),
                year: self::YEAR,
                emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            ),
            $this->ownerUser(),
        );

        return $response->activityId;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function countryResolver(): CarbonFootprintCountryResolver
    {
        return new CarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), PropertyRepositoryMother::inMemory());
    }
}
