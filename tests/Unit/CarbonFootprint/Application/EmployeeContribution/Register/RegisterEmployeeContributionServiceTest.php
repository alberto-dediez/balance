<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\Register;

use App\CarbonFootprint\Application\EmployeeContribution\Register\RegisterEmployeeContributionRequest;
use App\CarbonFootprint\Application\EmployeeContribution\Register\RegisterEmployeeContributionService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
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

class RegisterEmployeeContributionServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeRepository $employeeRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private RegisterEmployeeContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->employeeRepository = new InMemoryEmployeeRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new RegisterEmployeeContributionService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeRepository,
            $this->employeeContributionRepository,
        );
    }

    public function testRegisteringCreatesAPendingContributionForTheEmployee(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = EmployeeMother::fromNameAndEmail('John Doe', 'john@example.com');
        $this->employeeRepository->add($employee);

        $response = ($this->sut)(
            new RegisterEmployeeContributionRequest($eventCarbonFootprint->id(), $employee->id()),
            $this->ownerUser(),
        );

        $contribution = $this->employeeContributionRepository->find($response->employeeContributionId);
        $this->assertNotNull($contribution);
        $this->assertTrue($contribution->status()->isPending());
        $this->assertTrue($contribution->employeeId()?->equals($employee->id()));
        $this->assertSame('John Doe', $contribution->employeeName());
    }

    public function testMissingEventCarbonFootprintThrows(): void
    {
        $employee = EmployeeMother::registeredEmployee();
        $this->employeeRepository->add($employee);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new RegisterEmployeeContributionRequest(EventCarbonFootprintId::generate(), $employee->id()),
            $this->ownerUser(),
        );
    }

    public function testMissingEmployeeThrows(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new RegisterEmployeeContributionRequest($eventCarbonFootprint->id(), EmployeeId::generate()),
            $this->ownerUser(),
        );
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $employee = EmployeeMother::registeredEmployee();
        $this->employeeRepository->add($employee);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterEmployeeContributionRequest($eventCarbonFootprint->id(), $employee->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
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
