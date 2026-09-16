<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeParticipation\Reopen;

use App\CarbonFootprint\Application\EmployeeParticipation\Reopen\ReopenEmployeeParticipationRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\Reopen\ReopenEmployeeParticipationService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ReopenEmployeeParticipationServiceTest extends TestCase
{
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private ReopenEmployeeParticipationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ReopenEmployeeParticipationService($this->employeeParticipationRepository, $this->eventCarbonFootprintRepository);
    }

    public function testReopeningAClosedParticipationMakesItOpenAgain(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $participation->close(5.0);
        $this->employeeParticipationRepository->add($participation);

        ($this->sut)(new ReopenEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $reopened = $this->employeeParticipationRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertNotNull($reopened);
        $this->assertFalse($reopened->status()->isClosed());
        $this->assertNull($reopened->marginOfError());
    }

    public function testReopeningWithoutAParticipationDoesNothing(): void
    {
        ($this->sut)(new ReopenEmployeeParticipationRequest(EventCarbonFootprintId::generate()), $this->ownerUser());

        $this->addToAssertionCount(1);
    }

    public function testReopeningRemovesExtrapolatedEmployeeActivitiesSoTheyCanBeRegenerated(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $participation = EmployeeParticipationMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $participation->close(5.0);
        $this->employeeParticipationRepository->add($participation);

        $eventCarbonFootprint->addActivity(ActivityId::generate(), new ActivityPlacement(Phase::Assembly, Source::SuppliersAndStaffMobility, null, true));
        $manualActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($manualActivityId, new ActivityPlacement(Phase::Assembly, Source::SuppliersAndStaffMobility));
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        ($this->sut)(new ReopenEmployeeParticipationRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $remaining = array_values($eventCarbonFootprint->activities()->activityIds());
        $this->assertCount(1, $remaining);
        $this->assertTrue($remaining[0]->equals($manualActivityId));
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
