<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeParticipation\Close;

use App\CarbonFootprint\Application\Activity\ActivityAuthorizer;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\MobilityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\OvernightStayAnswerHandler;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\OvernightStayEmissionFactorSelector;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityRequest;
use App\CarbonFootprint\Application\Activity\Register\RegisterMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Register\RegisterOvernightStayActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateMobilityActivityService;
use App\CarbonFootprint\Application\Activity\Update\UpdateOvernightStayActivityService;
use App\CarbonFootprint\Application\CarbonFootprint\CarbonFootprintCountryResolver;
use App\CarbonFootprint\Application\CarbonFootprint\StageAnswersSynchronizer;
use App\CarbonFootprint\Application\EmployeeParticipation\Close\CloseEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Close\CloseEmployeeParticipationService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeContributionsAggregator;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipation;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOvernightStayRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CloseEmployeeParticipationServiceTest extends TestCase
{
    private const YEAR = 2026;

    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private InMemoryOvernightStayRepository $overnightStayRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private InMemoryActivityRepository $activityRepository;
    private Mobility $mobility;
    private CloseEmployeeParticipationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();
        $this->overnightStayRepository = new InMemoryOvernightStayRepository();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->mobility = MobilityMother::registeredMobility();
        $this->mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear(self::YEAR));
        $this->mobilityRepository = MobilityRepositoryMother::inMemory();
        $this->mobilityRepository->add($this->mobility);

        $authorizer = new ActivityAuthorizer();
        $calculator = new EmissionsCalculator();
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

        $this->sut = new CloseEmployeeParticipationService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeParticipationRepository,
            $this->employeeContributionRepository,
            new EmployeeContributionsAggregator(),
            new MobilityFactorBasisProvider($this->mobilityRepository),
            $this->overnightStayRepository,
            new StageAnswersSynchronizer($handlers, $this->countryResolver()),
        );
    }

    public function testClosingWithoutResponsesClosesParticipationWithoutSynchronizing(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $participation = $this->registeredParticipation($eventCarbonFootprint, 10);

        $manualActivityId = $this->registerActivity();
        $eventCarbonFootprint->addActivity($manualActivityId, new ActivityPlacement(Phase::Assembly, Source::SuppliersAndStaffMobility));

        ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $closed = $this->employeeParticipationRepository->find($participation->id());
        $this->assertNotNull($closed);
        $this->assertTrue($closed->status()->isClosed());

        $activityIds = $eventCarbonFootprint->activities()->filterByPhase(Phase::Assembly)->filterBySource(Source::SuppliersAndStaffMobility)->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($manualActivityId));
    }

    public function testClosingWithResponsesSynchronizesActivitiesByPhaseWithoutTouchingManualOnes(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, 10);

        $manualActivityId = $this->registerActivity();
        $eventCarbonFootprint->addActivity($manualActivityId, new ActivityPlacement(Phase::Assembly, Source::SuppliersAndStaffMobility));

        $overnightStay = OvernightStayMother::from('hotel', (string) OvernightStayId::generate(), CategoryOvernight::OrganizingTeam);
        $overnightStay->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Pernoctacion)->withYear(self::YEAR));
        $this->overnightStayRepository->add($overnightStay);

        $this->employeeContributionRepository->add($this->answeredContribution($eventCarbonFootprint));

        $response = ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertFalse($response->overnightStayCatalogMissing);

        foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
            $employeeMobilityIds = $eventCarbonFootprint->activities()
                ->filterByPhase($phase)
                ->filterBySource(Source::SuppliersAndStaffMobility)
                ->filterByFromEmployees(true)
                ->activityIds();
            $this->assertCount(1, $employeeMobilityIds);

            $employeeOvernightIds = $eventCarbonFootprint->activities()
                ->filterByPhase($phase)
                ->filterBySource(Source::SuppliersAndStaffAccommodation)
                ->filterByFromEmployees(true)
                ->activityIds();
            $this->assertCount(1, $employeeOvernightIds);
        }

        $manualIds = $eventCarbonFootprint->activities()
            ->filterByPhase(Phase::Assembly)
            ->filterBySource(Source::SuppliersAndStaffMobility)
            ->filterByFromEmployees(false)
            ->activityIds();
        $this->assertCount(1, $manualIds);
        $this->assertTrue($manualIds[0]->equals($manualActivityId));
    }

    public function testClosingWithHotelNightsButNoCatalogItemWarnsAndSkips(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, 10);

        $this->employeeContributionRepository->add($this->answeredContribution($eventCarbonFootprint));

        $response = ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertTrue($response->overnightStayCatalogMissing);

        $overnightIds = $eventCarbonFootprint->activities()->filterBySource(Source::SuppliersAndStaffAccommodation)->activityIds();
        $this->assertCount(0, $overnightIds);
    }

    public function testMarginOfErrorIsPersistedOnTheParticipation(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $participation = $this->registeredParticipation($eventCarbonFootprint, 50);

        $this->employeeContributionRepository->add($this->answeredContribution($eventCarbonFootprint));

        ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $closed = $this->employeeParticipationRepository->find($participation->id());
        $this->assertNotNull($closed);
        $this->assertGreaterThan(0.0, $closed->marginOfError());
    }

    public function testReClosingAfterReopeningRegeneratesActivities(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $participation = $this->registeredParticipation($eventCarbonFootprint, 10);

        $this->employeeContributionRepository->add($this->answeredContribution($eventCarbonFootprint));

        ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $firstIds = $eventCarbonFootprint->activities()->filterBySource(Source::SuppliersAndStaffMobility)->filterByFromEmployees(true)->filterByPhase(Phase::Assembly)->activityIds();
        $this->assertCount(1, $firstIds);

        $participation->reopen();
        $this->employeeParticipationRepository->add($participation);

        ($this->sut)(new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $secondIds = $eventCarbonFootprint->activities()->filterBySource(Source::SuppliersAndStaffMobility)->filterByFromEmployees(true)->filterByPhase(Phase::Assembly)->activityIds();
        $this->assertCount(1, $secondIds);
        $this->assertFalse($firstIds[0]->equals($secondIds[0]));
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, 10);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new CloseEmployeeParticipationRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function registeredParticipation(EventCarbonFootprint $eventCarbonFootprint, int $totalEmployees): EmployeeParticipation
    {
        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $participation->update($totalEmployees, ConfidenceLevel::Ninety);
        $this->employeeParticipationRepository->add($participation);

        return $participation;
    }

    private function answeredContribution(EventCarbonFootprint $eventCarbonFootprint): EmployeeContribution
    {
        return EmployeeContributionMother::answeredForAllPhases(
            eventCarbonFootprintId: (string) $eventCarbonFootprint->id(),
            mobilityId: (string) $this->mobility->id(),
            distanceKm: 100.0,
            vehicleOccupants: 1,
            days: 2,
            hotelNights: 1,
        );
    }

    private function registerActivity(): ActivityId
    {
        $response = (new RegisterMobilityActivityService(
            $this->activityRepository,
            new ActivityAuthorizer(),
            new MobilityEmissionFactorSelector($this->mobilityRepository),
            new EmissionsCalculator(),
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
