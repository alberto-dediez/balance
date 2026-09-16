<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Dashboard;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Application\Dashboard\DashboardCompensationsCalculator;
use App\CarbonFootprint\Application\Dashboard\EmissionsBreakdownCalculator;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardRequest;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\GetOwnerDashboardService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetOwnerDashboardServiceTest extends TestCase
{
    private const ACCOUNT_ID = 'account-id-test';

    public function testUseCase(): void
    {
        $eventRepository = EventRepositoryMother::inMemory();
        $footprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $companyRepository = CompanyRepositoryMother::inMemory();

        $property = PropertyMother::fromNameAndAccountAndId('Property A', self::ACCOUNT_ID, (string) PropertyId::generate());
        $company = CompanyMother::fromNameAndAccountAndId('Company A', self::ACCOUNT_ID, (string) CompanyId::generate());
        $propertyRepository->add($property);
        $companyRepository->add($company);

        $eventA = EventMother::from(
            name: 'Event A',
            id: (string) EventId::generate(),
            accountId: self::ACCOUNT_ID,
            propertyId: (string) $property->id(),
            companyId: (string) $company->id(),
        );
        $eventB = EventMother::fromNameAndAccountAndId('Event B', self::ACCOUNT_ID, (string) EventId::generate());
        $eventRepository->add($eventA);
        $eventRepository->add($eventB);

        $completedFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) $eventA->id(), self::ACCOUNT_ID);
        $completedFootprint->update(
            calculationPeriod: new DateRange(
                startDate: new \DateTimeImmutable('2026-05-01'),
                endDate: new \DateTimeImmutable('2026-05-03'),
            ),
        );
        $activity = MaterialActivityMother::registered();
        $activityRepository->save($activity);
        $completedFootprint->addActivity($activity->id(), new ActivityPlacement(Phase::Materials, Source::Materials));

        $completedFootprint->complete(
            totalEmissions: new Emissions(co2eq: '1000'),
            emissionsPerAttendee: new Emissions(co2eq: '10'),
        );

        $footprintRepository->add($completedFootprint);

        $startedFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) $eventB->id(), self::ACCOUNT_ID);
        $footprintRepository->add($startedFootprint);

        $sut = $this->createSUT($eventRepository, $footprintRepository, $activityRepository, $propertyRepository, $companyRepository);

        $response = $sut(
            new GetOwnerDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID)),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_ID)),
        );

        $this->assertEquals(2, $response->totalEvents);
        $this->assertEquals(2, $response->totalFootprints);
        $this->assertEquals(1, $response->completedFootprints);
        $this->assertEquals(1, $response->inProgressFootprints);
        $this->assertEquals(1000.0, $response->totalEmissionsCo2eq);
        $this->assertEquals(1000.0, $response->averageEmissionsPerEventCo2eq);
        $this->assertEquals(10.0, $response->averageEmissionsPerAttendeeCo2eq);
        $this->assertEquals([2026 => 1000.0], $response->emissionsByYear);
        $this->assertEquals([Phase::Materials->value => 15.0], $response->emissionsByPhase);
        $this->assertEquals([Source::Materials->value => 15.0], $response->emissionsBySource);
        $this->assertCount(1, $response->topEventsByEmissions);
        $this->assertEquals('Event A', $response->topEventsByEmissions[0]->event->name());
        $this->assertEquals(1000.0, $response->topEventsByEmissions[0]->co2eq);
        $this->assertCount(2, $response->latestFootprints);
        $this->assertEquals(['Property A' => 1000.0], $response->emissionsByProperty);
        $this->assertEquals(['Company A' => 1000.0], $response->emissionsByCompany);
    }

    public function testDeniesAccessToAnotherAccount(): void
    {
        $sut = $this->createSUT(
            EventRepositoryMother::inMemory(),
            EventCarbonFootprintRepositoryMother::inMemory(),
            ActivityRepositoryMother::inMemory(),
            PropertyRepositoryMother::inMemory(),
            CompanyRepositoryMother::inMemory(),
        );

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new GetOwnerDashboardRequest(accountId: new AccountId(self::ACCOUNT_ID)),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('another-account-id')),
        );
    }

    private function createSUT(
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRepository $activityRepository,
        PropertyRepository $propertyRepository,
        CompanyRepository $companyRepository,
    ): GetOwnerDashboardService {
        return new GetOwnerDashboardService(
            eventRepository: $eventRepository,
            eventCarbonFootprintRepository: $eventCarbonFootprintRepository,
            propertyRepository: $propertyRepository,
            companyRepository: $companyRepository,
            emissionsBreakdownCalculator: new EmissionsBreakdownCalculator($activityRepository),
            dashboardCompensationsCalculator: $this->createCompensationsCalculator(),
            authorizer: new EventCarbonFootprintAuthorizer(),
        );
    }

    private function createCompensationsCalculator(): DashboardCompensationsCalculator
    {
        return new DashboardCompensationsCalculator(
            projectContributionRepository: ProjectContributionRepositoryMother::inMemory(),
            contributionsAggregator: new ProjectContributionsAggregator(),
            projectRepository: ProjectRepositoryMother::inMemory(),
            attendeeSurveyRepository: AttendeeSurveyRepositoryMother::inMemory(),
            attendeeSurveyResponseRepository: AttendeeSurveyResponseRepositoryMother::inMemory(),
            tripCalculationLinkRepository: TripCalculationLinkRepositoryMother::inMemory(),
            tripCalculationRepository: TripCalculationRepositoryMother::inMemory(),
        );
    }
}
