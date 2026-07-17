<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EmployeeContribution\List;

use App\CarbonFootprint\Application\EmployeeContribution\List\ListEmployeeContributionsRequest;
use App\CarbonFootprint\Application\EmployeeContribution\List\ListEmployeeContributionsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEmployeeContributionsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEmployeeContributionRepository $employeeContributionRepository;
    private ListEmployeeContributionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->employeeContributionRepository = new InMemoryEmployeeContributionRepository();

        $this->sut = new ListEmployeeContributionsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->employeeContributionRepository,
        );
    }

    public function testListingReturnsContributionsForTheEventCarbonFootprint(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = EmployeeContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->employeeContributionRepository->add($contribution);

        $otherContribution = EmployeeContributionMother::registeredEmployeeContribution();
        $this->employeeContributionRepository->add($otherContribution);

        $response = ($this->sut)(new ListEmployeeContributionsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertCount(1, $response->employeeContributions);
        $this->assertTrue($response->employeeContributions[0]->id()->equals($contribution->id()));
    }

    public function testMissingEventCarbonFootprintThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ListEmployeeContributionsRequest(EventCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new ListEmployeeContributionsRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
