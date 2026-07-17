<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeParticipation\GetStats;

use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetEmployeeParticipationStatsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private GetEmployeeParticipationStatsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new GetEmployeeParticipationStatsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeParticipationRepository,
            $this->employeeContributionRepository,
        );
    }

    public function testStatsCountContributionsByStatusAndComputeAnsweredPercentage(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, 4);

        $answered = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $answered->answer();
        $this->employeeContributionRepository->add($answered);

        $ignored = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $ignored->ignore();
        $this->employeeContributionRepository->add($ignored);

        $pending = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($pending);

        $response = ($this->sut)(new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertSame(1, $response->answeredCount);
        $this->assertSame(1, $response->ignoredCount);
        $this->assertSame(1, $response->pendingCount);
        $this->assertSame(25.0, $response->answeredPercentage);
    }

    public function testAnsweredPercentageIsNullWhenNoTotalEmployees(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, null);

        $response = ($this->sut)(new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertNull($response->answeredPercentage);
    }

    public function testMissingEventCarbonFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetEmployeeParticipationStatsRequest(EventCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testMissingEmployeeParticipationThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $this->registeredParticipation($eventCarbonFootprint, 4);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function registeredParticipation(EventCarbonFootprint $eventCarbonFootprint, ?int $totalEmployees): void
    {
        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');

        if (null !== $totalEmployees) {
            $participation->update($totalEmployees, null);
        }

        $this->employeeParticipationRepository->add($participation);
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
