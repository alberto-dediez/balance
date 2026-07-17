<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\AssignEmployees;

use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignedEmployee;
use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignEmployeesRequest;
use App\CarbonFootprint\Application\EmployeeContribution\AssignEmployees\AssignEmployeesService;
use App\CarbonFootprint\Application\EmployeeContribution\Register\RegisterEmployeeContributionService;
use App\CarbonFootprint\Application\EmployeeParticipation\Register\RegisterEmployeeParticipationService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\Employee;
use App\HumanResources\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AssignEmployeesServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeRepository $employeeRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private InMemoryEmployeeParticipationRepository $employeeParticipationRepository;
    private AssignEmployeesService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->employeeRepository = new InMemoryEmployeeRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();
        $this->employeeParticipationRepository = new InMemoryEmployeeParticipationRepository();

        $registerEmployeeContribution = new RegisterEmployeeContributionService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeRepository,
            $this->employeeContributionRepository,
        );

        $registerEmployeeParticipation = new RegisterEmployeeParticipationService(
            $this->employeeParticipationRepository,
            $this->eventCarbonFootprintRepository,
        );

        $this->sut = new AssignEmployeesService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeContributionRepository,
            $registerEmployeeContribution,
            $registerEmployeeParticipation,
            $this->employeeParticipationRepository,
        );
    }

    public function testAssigningANewEmployeeRegistersAPendingContribution(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 10, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );

        $contributions = $this->employeeContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertTrue($contributions[0]->status()->isPending());
        $this->assertSame($employee->name(), $contributions[0]->employeeName());
    }

    public function testUpdatesParticipationWithTotalEmployeesAndConfidenceLevel(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 25, ConfidenceLevel::Ninety),
            $this->ownerUser(),
        );

        $participation = $this->employeeParticipationRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertNotNull($participation);
        $this->assertSame(25, $participation->totalEmployees());
        $this->assertSame(ConfidenceLevel::Ninety, $participation->confidenceLevel());
    }

    public function testUnassigningAnEmployeeIgnoresItsContribution(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 10, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [], 10, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );

        $contributions = $this->employeeContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertTrue($contributions[0]->status()->isIgnored());
    }

    public function testReassigningAPreviouslyUnassignedEmployeeUnignoresIt(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();

        ($this->sut)(new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 10, ConfidenceLevel::NinetyFive), $this->ownerUser());
        ($this->sut)(new AssignEmployeesRequest($eventCarbonFootprint->id(), [], 10, ConfidenceLevel::NinetyFive), $this->ownerUser());
        ($this->sut)(new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 10, ConfidenceLevel::NinetyFive), $this->ownerUser());

        $contributions = $this->employeeContributionRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());
        $this->assertCount(1, $contributions);
        $this->assertFalse($contributions[0]->status()->isIgnored());
    }

    public function testAssigningEmployeesMarksTheEventCarbonFootprintAsEmployeesAnswered(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id())], 10, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );

        $this->assertTrue($eventCarbonFootprint->status()->isEmployeesAnswered());
    }

    public function testTotalEmployeesBelowOneThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [], 0, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );
    }

    public function testTotalEmployeesBelowSelectedEmployeesThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = $this->registeredEmployee();
        $otherEmployee = $this->registeredEmployee();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [new AssignedEmployee($employee->id()), new AssignedEmployee($otherEmployee->id())], 1, ConfidenceLevel::NinetyFive),
            $this->ownerUser(),
        );
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new AssignEmployeesRequest($eventCarbonFootprint->id(), [], 10, ConfidenceLevel::NinetyFive),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function registeredEmployee(): Employee
    {
        $employee = EmployeeMother::forAccount('account-id');
        $this->employeeRepository->add($employee);

        return $employee;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
