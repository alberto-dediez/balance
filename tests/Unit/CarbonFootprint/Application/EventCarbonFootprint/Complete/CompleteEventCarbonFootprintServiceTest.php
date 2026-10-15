<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Complete;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\MobilityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\OvernightStayAnswerHandler;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\EmployeeParticipation\Close\CloseEmployeeParticipationService;
use App\CarbonFootprint\Application\EventCarbonFootprint\Complete\CompleteEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Complete\CompleteEventCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeContributionsAggregator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\Activity\EmissionsCalculatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompleteEventCarbonFootprintServiceTest extends TestCase
{
    private InMemoryActivityRepository $activityRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEventRepository $eventRepository;
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private CompleteEventCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->eventRepository = EventRepositoryMother::inMemory();
        $this->attendeeSurveyRepository = AttendeeSurveyRepositoryMother::inMemory();
        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();

        $this->sut = new CompleteEventCarbonFootprintService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->activityRepository,
            $this->eventRepository,
            $this->attendeeSurveyRepository,
            $this->employeeParticipationRepository,
            $this->closeEmployeeParticipationService(),
        );
    }

    private function closeEmployeeParticipationService(): CloseEmployeeParticipationService
    {
        $overnightStayRepository = new InMemoryOvernightStayRepository();
        $authorizer = new ActivityAuthorizer();
        $calculator = EmissionsCalculatorMother::create();
        $mobilitySelector = new MobilityEmissionFactorSelector($this->mobilityRepository);
        $overnightStaySelector = new OvernightStayEmissionFactorSelector($overnightStayRepository);

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

        return new CloseEmployeeParticipationService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeParticipationRepository,
            $this->employeeContributionRepository,
            new EmployeeContributionsAggregator(),
            new MobilityFactorBasisProvider(new InMemoryMobilityRepository()),
            $overnightStayRepository,
            new StageAnswersSynchronizer($handlers, $this->countryResolver()),
        );
    }

    public function testSumsEmissionsAcrossPhasesAndSourcesAndCompletes(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-1');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-1');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '10');
        $this->addActivity($eventCarbonFootprint, Phase::Celebration, Source::AttendeesMobility, '20');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame('30', $eventCarbonFootprint->totalEmissions()->co2eq());
        $this->assertSame(EventCarbonFootprintStatus::Completed, $eventCarbonFootprint->status());
    }

    public function testPerAttendeeUsesRealAttendeesFromClosedSurvey(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-1');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-1');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '30');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $survey = AttendeeSurveyMother::fromEventId('event-1');
        $survey->close(3, ConfidenceLevel::Ninety, 0.05);
        $this->attendeeSurveyRepository->add($survey);

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame('10', $eventCarbonFootprint->emissionsPerAttendee()->co2eq());
    }

    public function testPerAttendeeFallsBackToEstimatedAttendeesWithoutSurvey(): void
    {
        $event = new Event(id: new EventId('event-1'));
        $event->register(
            name: 'Event',
            location: null,
            propertyId: null,
            companyId: null,
            size: null,
            estimatedAttendees: 5,
            sector: null,
            typology: null,
            assembly: new DateRange(),
            celebration: new DateRange(),
            breakdown: new DateRange(),
            accountId: new AccountId('account-id'),
        );
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-1');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '25');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame('5', $eventCarbonFootprint->emissionsPerAttendee()->co2eq());
    }

    public function testPerAttendeeIsZeroWhenNeitherSurveyNorEstimatedAttendeesAreAvailable(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-without-event');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '25');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame('0', $eventCarbonFootprint->emissionsPerAttendee()->co2eq());
        $this->assertSame(EventCarbonFootprintStatus::Completed, $eventCarbonFootprint->status());
    }

    public function testCompletingClosesOpenEmployeeParticipationAndIncludesExtrapolatedEmissions(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-1');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-1', 'account-id');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '10');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $mobility = MobilityMother::registeredMobility();
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($eventCarbonFootprint->calculationYear()));
        $this->mobilityRepository->add($mobility);

        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $participation->update(10, ConfidenceLevel::Ninety);
        $this->employeeParticipationRepository->add($participation);

        $this->employeeContributionRepository->add(EmployeeContributionMother::answeredForAllPhases(
            eventCarbonFootprintId: (string) $eventCarbonFootprint->id(),
            mobilityId: (string) $mobility->id(),
            distanceKm: 100.0,
        ));

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $closedParticipation = $this->employeeParticipationRepository->find($participation->id());
        $this->assertNotNull($closedParticipation);
        $this->assertTrue($closedParticipation->status()->isClosed());

        $employeeActivityIds = $eventCarbonFootprint->activities()->filterByFromEmployees(true)->activityIds();
        $this->assertCount(3, $employeeActivityIds);

        $this->assertEqualsWithDelta(4510.0, (float) $eventCarbonFootprint->totalEmissions()->co2eq(), 0.001);
        $this->assertSame(EventCarbonFootprintStatus::Completed, $eventCarbonFootprint->status());
    }

    public function testEventCarbonFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new CompleteEventCarbonFootprintRequest(EventCarbonFootprintMother::random()->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    public function testRecalculatingTwiceIsIdempotent(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-1');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId('event-1');
        $this->addActivity($eventCarbonFootprint, Phase::Materials, Source::Materials, '10');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());
        $firstTotal = $eventCarbonFootprint->totalEmissions()->co2eq();

        ($this->sut)(new CompleteEventCarbonFootprintRequest($eventCarbonFootprint->id()), $this->ownerUser());
        $secondTotal = $eventCarbonFootprint->totalEmissions()->co2eq();

        $this->assertSame($firstTotal, $secondTotal);
        $this->assertSame(EventCarbonFootprintStatus::Completed, $eventCarbonFootprint->status());
    }

    private function addActivity(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, Source $source, string $co2eq): MaterialActivity
    {
        $activity = MaterialActivityMother::registered(new AccountId('account-id'));
        $activity->update($activity->materialId(), $activity->condition(), $activity->emissionFactor(), new Emissions($co2eq, $co2eq, '0', '0'), $activity->quantity());
        $this->activityRepository->save($activity);

        $eventCarbonFootprint->addActivity($activity->id(), new ActivityPlacement($phase, $source));

        return $activity;
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
