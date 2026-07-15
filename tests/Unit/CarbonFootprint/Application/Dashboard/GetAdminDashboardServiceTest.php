<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\Dashboard\EmissionsBreakdownCalculator;
use App\CarbonFootprint\Application\Dashboard\GetAdminDashboard\GetAdminDashboardRequest;
use App\CarbonFootprint\Application\Dashboard\GetAdminDashboard\GetAdminDashboardService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class GetAdminDashboardServiceTest extends TestCase
{
    private const ACCOUNT_A_ID = 'account-a-id-test';

    private const ACCOUNT_B_ID = 'account-b-id-test';

    public function testUseCase(): void
    {
        $accountRepository = AccountRepositoryMother::inMemory();
        $userRepository = UserRepositoryMother::inMemory();
        $eventRepository = EventRepositoryMother::inMemory();
        $footprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $activityRepository = ActivityRepositoryMother::inMemory();

        $accountA = AccountMother::fromNameAndId('Account A', self::ACCOUNT_A_ID);
        $accountB = AccountMother::fromNameAndId('Account B', self::ACCOUNT_B_ID);
        $accountRepository->add($accountA);
        $accountRepository->add($accountB);

        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner-a', Role::Owner, new AccountId(self::ACCOUNT_A_ID)));
        $userRepository->add(UserMother::fromUsernameAndRoleAndAccount('owner-b', Role::Owner, new AccountId(self::ACCOUNT_B_ID)));

        $eventA = EventMother::fromNameAndAccountAndId('Event A', self::ACCOUNT_A_ID, (string) EventId::generate());
        $eventB = EventMother::fromNameAndAccountAndId('Event B', self::ACCOUNT_B_ID, (string) EventId::generate());
        $eventRepository->add($eventA);
        $eventRepository->add($eventB);

        $completedFootprintA = EventCarbonFootprintMother::fromEventIdAndAccount((string) $eventA->id(), self::ACCOUNT_A_ID);
        $completedFootprintA->update(
            calculationPeriod: new DateRange(
                startDate: new \DateTimeImmutable('2026-05-01'),
                endDate: new \DateTimeImmutable('2026-05-03'),
            ),
        );
        $activity = MaterialActivityMother::registered();
        $activityRepository->save($activity);
        $completedFootprintA->addActivity($activity->id(), Phase::Materials, Source::Materials);

        $completedFootprintA->complete(
            totalEmissions: new Emissions(co2eq: '1000'),
            emissionsPerAttendee: new Emissions(co2eq: '10'),
        );

        $footprintRepository->add($completedFootprintA);

        $completedFootprintB = EventCarbonFootprintMother::fromEventIdAndAccount((string) $eventB->id(), self::ACCOUNT_B_ID);
        $completedFootprintB->update(
            calculationPeriod: new DateRange(
                startDate: new \DateTimeImmutable('2026-06-01'),
                endDate: new \DateTimeImmutable('2026-06-02'),
            ),
        );
        $completedFootprintB->complete(
            totalEmissions: new Emissions(co2eq: '3000'),
            emissionsPerAttendee: new Emissions(co2eq: '30'),
        );
        $footprintRepository->add($completedFootprintB);

        $startedFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) $eventA->id(), self::ACCOUNT_A_ID);
        $footprintRepository->add($startedFootprint);

        $sut = $this->createSUT($accountRepository, $userRepository, $eventRepository, $footprintRepository, $activityRepository);

        $response = $sut(new GetAdminDashboardRequest(), UserMother::adminUser());

        $this->assertEquals(2, $response->totalAccounts);
        $this->assertEquals(2, $response->activeAccounts);
        $this->assertEquals(2, $response->totalUsers);
        $this->assertEquals(2, $response->totalEvents);
        $this->assertEquals(3, $response->totalFootprints);
        $this->assertEquals(2, $response->completedFootprints);
        $this->assertEquals(1, $response->inProgressFootprints);
        $this->assertEquals(4000.0, $response->totalEmissionsCo2eq);
        $this->assertEquals(2000.0, $response->averageEmissionsPerEventCo2eq);
        $this->assertEquals([2026 => 4000.0], $response->emissionsByYear);
        $this->assertEquals([Phase::Materials->value => 15.0], $response->emissionsByPhase);
        $this->assertEquals([Source::Materials->value => 15.0], $response->emissionsBySource);
        $this->assertCount(2, $response->topAccountsByEmissions);
        $this->assertEquals('Account B', $response->topAccountsByEmissions[0]->account->name());
        $this->assertEquals(3000.0, $response->topAccountsByEmissions[0]->co2eq);
        $this->assertEquals('Account A', $response->topAccountsByEmissions[1]->account->name());
        $this->assertCount(3, $response->latestFootprints);
        $this->assertEquals('Account A', $response->latestFootprints[0]->account?->name());
    }

    public function testDeniesAccessToNonAdminUsers(): void
    {
        $sut = $this->createSUT(
            AccountRepositoryMother::inMemory(),
            UserRepositoryMother::inMemory(),
            EventRepositoryMother::inMemory(),
            EventCarbonFootprintRepositoryMother::inMemory(),
            ActivityRepositoryMother::inMemory(),
        );

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new GetAdminDashboardRequest(),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT_A_ID)),
        );
    }

    private function createSUT(
        AccountRepository $accountRepository,
        UserRepository $userRepository,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        ActivityRepository $activityRepository,
    ): GetAdminDashboardService {
        return new GetAdminDashboardService(
            accountRepository: $accountRepository,
            userRepository: $userRepository,
            eventRepository: $eventRepository,
            eventCarbonFootprintRepository: $eventCarbonFootprintRepository,
            emissionsBreakdownCalculator: new EmissionsBreakdownCalculator($activityRepository),
            authorizer: new EventCarbonFootprintAuthorizer(),
        );
    }
}
